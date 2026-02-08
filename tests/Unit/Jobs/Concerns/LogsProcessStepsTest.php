<?php

declare(strict_types=1);

use App\Jobs\Concerns\LogsProcessSteps;
use App\Models\CloningRun;

beforeEach(function (): void {
    $this->job = new class
    {
        use LogsProcessSteps;

        public string $tableName = 'users';

        public ?CloningRun $run = null;

        public function batch(): ?object
        {
            return null;
        }

        /**
         * Expose the private method for testing.
         */
        public function testShouldLog(string $tableName, int $currentPercent): bool
        {
            return $this->shouldLogProgressToDatabase($tableName, $currentPercent);
        }

        /**
         * Expose lastLoggedTime for manipulation in tests.
         */
        public function setLastLoggedTime(string $tableName, float $time): void
        {
            $this->lastLoggedTime[$tableName] = $time;
        }

        /**
         * Expose the threshold for testing.
         */
        public function getThresholdSeconds(): int
        {
            return $this->progressLogThresholdSeconds;
        }
    };
});

it('always logs the first progress for a table', function (): void {
    expect($this->job->testShouldLog('users', 5))->toBeTrue();
});

it('always logs progress at 100 percent', function (): void {
    // Set a recent last logged time so time-based throttle would block it
    $this->job->setLastLoggedTime('users', microtime(true));

    expect($this->job->testShouldLog('users', 100))->toBeTrue();
});

it('does not log progress within the time threshold', function (): void {
    // Simulate a recent log (just now)
    $this->job->setLastLoggedTime('users', microtime(true));

    expect($this->job->testShouldLog('users', 50))->toBeFalse();
});

it('logs progress after the time threshold has elapsed', function (): void {
    // Simulate a log that happened 11 seconds ago
    $this->job->setLastLoggedTime('users', microtime(true) - 11);

    expect($this->job->testShouldLog('users', 50))->toBeTrue();
});

it('tracks tables independently for time-based throttling', function (): void {
    // 'users' was logged recently, 'orders' was never logged
    $this->job->setLastLoggedTime('users', microtime(true));

    expect($this->job->testShouldLog('users', 30))->toBeFalse()
        ->and($this->job->testShouldLog('orders', 30))->toBeTrue();
});

it('has a default threshold of 10 seconds', function (): void {
    expect($this->job->getThresholdSeconds())->toBe(10);
});

it('does not log progress at exactly the threshold boundary', function (): void {
    // Simulate a log that happened exactly 9.9 seconds ago (just under threshold)
    $this->job->setLastLoggedTime('users', microtime(true) - 9.9);

    expect($this->job->testShouldLog('users', 50))->toBeFalse();
});

it('logs progress at exactly the threshold', function (): void {
    // Simulate a log that happened exactly 10 seconds ago
    $this->job->setLastLoggedTime('users', microtime(true) - 10);

    expect($this->job->testShouldLog('users', 50))->toBeTrue();
});
