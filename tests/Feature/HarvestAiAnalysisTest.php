<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Payload;
use App\Models\Pond;
use App\Models\PondCycle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HarvestAiAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['services.openrouter.key' => 'test-openrouter-key']);
    }

    public function test_user_harvest_ai_analysis_uses_selected_users_selected_pond_data_only(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Latest cycle improved because Tilapia increased and Bangus was added while the selected pond telemetry stayed within the provided range.',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia', 'Catfish'],
        ]);
        $sameUserOtherPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 2.50,
            'fish_type' => ['Shrimp'],
        ]);
        $otherUserPond = Pond::create([
            'user_id' => $otherUser->id,
            'hectares' => 3.50,
            'fish_type' => ['Crab'],
        ]);

        $this->createCompletedCycle($selectedPond, $user, 1, now()->subDays(80), now()->subDays(50), [
            ['species' => 'Tilapia', 'hatching_kg' => 90, 'expected_harvest_kg' => 100, 'unit' => 'kg'],
            ['species' => 'Catfish', 'hatching_kg' => 25, 'expected_harvest_kg' => 25, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 100, 'unit' => 'kg'],
            ['species' => 'Catfish', 'harvest_kg' => 20, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($selectedPond, $user, 2, now()->subDays(40), now()->subDays(10), [
            ['species' => 'Tilapia', 'hatching_kg' => 120, 'expected_harvest_kg' => 130, 'unit' => 'kg'],
            ['species' => 'Bangus', 'hatching_kg' => 35, 'expected_harvest_kg' => 40, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 140, 'unit' => 'kg'],
            ['species' => 'Bangus', 'harvest_kg' => 35, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($sameUserOtherPond, $user, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Shrimp', 'harvest_kg' => 999, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($otherUserPond, $otherUser, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Crab', 'harvest_kg' => 888, 'unit' => 'kg'],
        ]);

        $this->createPayload($selectedPond, $user, now()->subDays(70), [
            'ph' => 7.2,
            'water_temp' => 29.5,
            'mq_ratio' => 0.03,
        ]);
        $this->createPayload($sameUserOtherPond, $user, now()->subDays(70), [
            'ph' => 9.9,
            'water_temp' => 99,
            'mq_ratio' => 9.99,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $selectedPond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('canAnalyze', true);
        $response->assertJsonPath('analysis', 'Latest cycle improved because Tilapia increased and Bangus was added while the selected pond telemetry stayed within the provided range.');

        Http::assertSent(function ($request) use ($selectedPond) {
            $prompt = data_get($request->data(), 'messages.1.content', '');

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && data_get($request->data(), 'model') === 'tencent/hy3-preview:free'
                && data_get($request->data(), 'reasoning.enabled') === null
                && str_contains($prompt, '"id": '.$selectedPond->id)
                && str_contains($prompt, 'Tilapia')
                && str_contains($prompt, 'Bangus')
                && str_contains($prompt, '"avg": 7.2')
                && str_contains($prompt, '"avg": 29.5')
                && str_contains($prompt, '"avg": 0.03')
                && ! str_contains($prompt, 'Shrimp')
                && ! str_contains($prompt, 'Crab')
                && ! str_contains($prompt, '999')
                && ! str_contains($prompt, '888');
        });

        $cachedResponse = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $selectedPond->id,
        ]));

        $cachedResponse->assertOk();
        $cachedResponse->assertJsonPath('cached', true);
        Http::assertSentCount(1);
    }

    public function test_openrouter_text_field_is_used_when_message_content_is_empty(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => '',
                            'reasoning' => "Summary:\nThe latest cycle was higher because Tilapia harvest rose from 80.00 kg to 110.00 kg.",
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('fallback', false);
        $response->assertJsonPath('analysis', 'The latest cycle was higher because Tilapia harvest rose from 80.00 kg to 110.00 kg.');
        Http::assertSentCount(1);
    }

    public function test_openrouter_meta_intro_is_removed_and_analysis_is_limited_to_three_sentences(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "Got it, let's tackle this step by step. First, I need to follow the exact structure given. Based on the provided JSON, latest harvest was 110.00 kg versus 80.00 kg previously. The increase likely came from higher Tilapia harvest while pH, temperature, and ammonia data did not show a clear limiting factor. Keep monitoring water quality before the next cycle. This extra sentence should be removed.",
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $analysis = $response->json('analysis');

        $this->assertStringNotContainsString('Got it', $analysis);
        $this->assertStringNotContainsString('First, I need', $analysis);
        $this->assertStringNotContainsString('Based on the provided JSON', $analysis);
        $this->assertStringContainsString('Latest harvest was 110.00 kg versus 80.00 kg previously.', $analysis);
        $this->assertLessThanOrEqual(3, $this->sentenceCount($analysis));
        $this->assertStringNotContainsString('This extra sentence should be removed.', $analysis);
    }

    public function test_template_style_sentences_are_removed_from_openrouter_analysis(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'The analysis should explain that one cycle performed better than the other. Format: Summary, Reason, Recommendation. The latest cycle produced 110.00 kg versus 80.00 kg previously because Tilapia harvest increased. Keep ammonia and pH stable next cycle.',
                        ],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $analysis = $response->json('analysis');

        $this->assertStringNotContainsString('The analysis should', $analysis);
        $this->assertStringNotContainsString('Format:', $analysis);
        $this->assertStringNotContainsString('Summary, Reason, Recommendation', $analysis);
        $this->assertStringContainsString('The latest cycle produced 110.00 kg versus 80.00 kg previously because Tilapia harvest increased.', $analysis);
        $this->assertStringNotContainsString('Keep ammonia and pH stable next cycle.', $analysis);
        $this->assertLessThanOrEqual(3, $this->sentenceCount($analysis));
    }

    public function test_template_only_openrouter_response_uses_local_fallback_analysis(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'The analysis should explain the harvest comparison in 2 to 3 sentences. This sentence should mention pH and ammonia.',
                            ],
                        ],
                    ],
                ])
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'Use this format: Summary, Reason, Recommendation. The output must be concise.',
                            ],
                        ],
                    ],
                ]),
        ]);

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('fallback', true);
        $analysis = $response->json('analysis');

        $this->assertStringContainsString('The latest cycle had a better harvest, producing 110.00 kg versus 80.00 kg', $analysis);
        $this->assertStringNotContainsString('The analysis should', $analysis);
        $this->assertStringNotContainsString('Use this format', $analysis);
        $this->assertStringNotContainsString('For the next cycle', $analysis);
        Http::assertSentCount(2);
    }

    public function test_prompt_reasoning_openrouter_response_uses_local_fallback_analysis(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'First, the question says if previous cycle harvested more, explain why previous was better. Wait, previous harvest is 18kg, latest is 3kg, so previous is higher. Wait no, wait the user said: "If the previous cycle harvested more, explain why the previous cycle may have performed better than the latest cycle." This is a format question. The output will be the analysis itself.',
                            ],
                        ],
                    ],
                ])
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => 'The question asks for the final analysis. The output should compare the cycles.',
                            ],
                        ],
                    ],
                ]),
        ]);

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [
            ['species' => 'Tilapia', 'hatching_kg' => 25, 'expected_harvest_kg' => 20, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 18, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [
            ['species' => 'Tilapia', 'hatching_kg' => 5, 'expected_harvest_kg' => 8, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 3, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('fallback', true);
        $analysis = $response->json('analysis');

        $this->assertStringContainsString('The previous cycle had a better harvest, producing 18.00 kg versus 3.00 kg', $analysis);
        $this->assertStringContainsString('Tilapia harvest falling from 18.00 kg to 3.00 kg', $analysis);
        $this->assertStringNotContainsString('First, the question says', $analysis);
        $this->assertStringNotContainsString('format question', $analysis);
        $this->assertStringNotContainsString('The output will be the analysis itself', $analysis);
        $this->assertLessThanOrEqual(3, $this->sentenceCount($analysis));
        Http::assertSentCount(2);
    }

    public function test_empty_openrouter_response_retries_then_uses_local_fallback_analysis(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => '',
                                'reasoning' => '',
                                'reasoning_details' => [],
                            ],
                        ],
                    ],
                ])
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => '',
                            ],
                        ],
                    ],
                ]),
        ]);

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia', 'Bangus'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [
            ['species' => 'Tilapia', 'hatching_kg' => 80, 'expected_harvest_kg' => 90, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [
            ['species' => 'Tilapia', 'hatching_kg' => 100, 'expected_harvest_kg' => 105, 'unit' => 'kg'],
            ['species' => 'Bangus', 'hatching_kg' => 30, 'expected_harvest_kg' => 35, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
            ['species' => 'Bangus', 'harvest_kg' => 25, 'unit' => 'kg'],
        ]);

        $this->createPayload($pond, $user, now()->subDays(35), [
            'ph' => 7.4,
            'water_temp' => 30.1,
            'mq_ratio' => 0.04,
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('fallback', true);
        $this->assertStringContainsString('The latest cycle had a better harvest, producing 135.00 kg versus 80.00 kg', $response->json('analysis'));
        $this->assertStringContainsString('Bangus', $response->json('analysis'));
        $this->assertStringContainsString('temperature 30.10 C', $response->json('analysis'));
        $this->assertLessThanOrEqual(3, $this->sentenceCount($response->json('analysis')));
        Http::assertSentCount(2);
    }

    public function test_fallback_directly_explains_when_previous_cycle_harvested_more(): void
    {
        config(['services.openrouter.key' => null]);
        Http::fake();

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia', 'Bangus'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(90), now()->subDays(60), [
            ['species' => 'Tilapia', 'hatching_kg' => 130, 'expected_harvest_kg' => 140, 'unit' => 'kg'],
            ['species' => 'Bangus', 'hatching_kg' => 40, 'expected_harvest_kg' => 45, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 140, 'unit' => 'kg'],
            ['species' => 'Bangus', 'harvest_kg' => 45, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(45), now()->subDays(20), [
            ['species' => 'Tilapia', 'hatching_kg' => 90, 'expected_harvest_kg' => 100, 'unit' => 'kg'],
        ], [
            ['species' => 'Tilapia', 'harvest_kg' => 95, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('fallback', true);
        $analysis = $response->json('analysis');

        $this->assertStringContainsString('The previous cycle had a better harvest, producing 185.00 kg versus 95.00 kg', $analysis);
        $this->assertStringContainsString('Bangus dropping from 45.00 kg to no recorded latest harvest', $analysis);
        $this->assertStringNotContainsString('For the next cycle', $analysis);
        $this->assertLessThanOrEqual(3, $this->sentenceCount($analysis));
        Http::assertNothingSent();
    }

    public function test_user_harvest_ai_analysis_rejects_ponds_not_owned_by_authenticated_user(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherPond = Pond::create([
            'user_id' => $otherUser->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $otherPond->id,
        ]));

        $response->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_harvest_ai_analysis_does_not_call_openrouter_without_two_completed_cycles(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 100, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('canAnalyze', false);
        $response->assertJsonPath('message', 'Not enough completed harvest cycles to generate AI analysis yet.');
        Http::assertNothingSent();
    }

    public function test_harvest_ai_analysis_handles_missing_openrouter_key_without_calling_api(): void
    {
        config(['services.openrouter.key' => null]);
        Http::fake();

        $user = User::factory()->create();
        $pond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);

        $this->createCompletedCycle($pond, $user, 1, now()->subDays(80), now()->subDays(50), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($pond, $user, 2, now()->subDays(40), now()->subDays(10), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($user)->getJson(route('dashboard.harvest-analysis', [
            'pond_id' => $pond->id,
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('canAnalyze', true);
        $response->assertJsonPath('message', 'AI harvest analysis is unavailable because the OpenRouter API key is not configured.');
        $response->assertJsonPath('fallback', true);
        $this->assertStringContainsString('The latest cycle had a better harvest, producing 110.00 kg versus 80.00 kg', $response->json('analysis'));
        $this->assertLessThanOrEqual(3, $this->sentenceCount($response->json('analysis')));
        Http::assertNothingSent();
    }

    public function test_admin_harvest_ai_analysis_uses_requested_user_and_pond_scope(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'The latest cycle had a better harvest because Tilapia rose from 80.00 kg to 110.00 kg.',
                        ],
                    ],
                ],
            ]),
        ]);

        $admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selectedPond = Pond::create([
            'user_id' => $user->id,
            'hectares' => 1.25,
            'fish_type' => ['Tilapia'],
        ]);
        $otherUserPond = Pond::create([
            'user_id' => $otherUser->id,
            'hectares' => 4.00,
            'fish_type' => ['Shrimp'],
        ]);

        $this->createCompletedCycle($selectedPond, $user, 1, now()->subDays(90), now()->subDays(60), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 80, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($selectedPond, $user, 2, now()->subDays(50), now()->subDays(20), [], [
            ['species' => 'Tilapia', 'harvest_kg' => 110, 'unit' => 'kg'],
        ]);
        $this->createCompletedCycle($otherUserPond, $otherUser, 2, now()->subDays(50), now()->subDays(20), [], [
            ['species' => 'Shrimp', 'harvest_kg' => 999, 'unit' => 'kg'],
        ]);

        $response = $this->actingAs($admin, 'admin')->getJson(route('admin.users.ponds.harvest-analysis', [
            'user' => $user,
            'pond' => $selectedPond,
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('analysis', 'The latest cycle had a better harvest because Tilapia rose from 80.00 kg to 110.00 kg.');

        Http::assertSent(function ($request) {
            $prompt = data_get($request->data(), 'messages.1.content', '');

            return str_contains($prompt, 'Tilapia')
                && ! str_contains($prompt, 'Shrimp')
                && ! str_contains($prompt, '999');
        });
    }

    private function createCompletedCycle(
        Pond $pond,
        User $user,
        int $cycleNumber,
        $startedAt,
        $completedAt,
        array $speciesData,
        array $harvestData
    ): PondCycle {
        return PondCycle::create([
            'pond_id' => $pond->id,
            'user_id' => $user->id,
            'cycle_number' => $cycleNumber,
            'status' => 'completed',
            'hatching_started_at' => $startedAt->toDateString(),
            'harvest_date' => $completedAt->toDateString(),
            'completed_at' => $completedAt->toDateString(),
            'species_data' => $speciesData,
            'harvest_data' => $harvestData,
        ]);
    }

    private function createPayload(Pond $pond, User $user, $recordedAt, array $payload): Payload
    {
        $record = Payload::create([
            'user_id' => $user->id,
            'pond_id' => $pond->id,
            'payload' => $payload,
        ]);

        $record->forceFill([
            'created_at' => $recordedAt,
            'updated_at' => $recordedAt,
        ])->save();

        return $record;
    }

    private function sentenceCount(string $text): int
    {
        return count(preg_split('/(?<=[.!?])\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }
}
