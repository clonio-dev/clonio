<?php

declare(strict_types=1);

namespace App\Data;

final readonly class TableMetricsData
{
    public function __construct(
        public int $rowsCount,
        public int $dataSizeInBytes,
    )
    { }

    public function dataSizeInMB(): float
    {
        return $this->dataSizeInBytes / 1024 / 1024;
    }

    /**
     * Get data size in human-readable format
     */
    public function humanReadableDataSize(): string
    {
        return $this->formatBytes($this->dataSizeInBytes);
    }

    public function estimatedDurationInSeconds(int $rowsPerSecond = 1000): float
    {
        return $this->rowsCount / $rowsPerSecond;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return round($kb, 2) . ' KB';
        }

        $mb = $kb / 1024;
        if ($mb < 1024) {
            return round($mb, 2) . ' MB';
        }

        $gb = $mb / 1024;

        return round($gb, 2) . ' GB';
    }
}
