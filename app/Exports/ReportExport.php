<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ReportExport implements FromArray
{
    public function __construct(protected array $report, protected array $filters)
    {
    }

    public function array(): array
    {
        $rows = [
            [$this->report['title'] ?? 'Report'],
            ['Report Type', ucwords(str_replace('_', ' ', $this->filters['report_type'] ?? 'report'))],
            ['Period', ucwords($this->filters['period'] ?? 'custom')],
            ['Start Date', $this->filters['start_date'] ?? ''],
            ['End Date', $this->filters['end_date'] ?? ''],
            [],
            ['Summary'],
            ['Metric', 'Value'],
        ];

        foreach ($this->report['summary'] ?? [] as $item) {
            $rows[] = [
                $item['label'] ?? '',
                $item['value'] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = [$this->report['table']['title'] ?? 'Details'];
        $rows[] = $this->report['table']['columns'] ?? [];

        foreach ($this->report['table']['rows'] ?? [] as $row) {
            $rows[] = $row;
        }

        foreach ($this->report['sections'] ?? [] as $section) {
            $rows[] = [];
            $rows[] = [$section['title'] ?? 'Section'];
            $rows[] = ['Metric', 'Value'];

            foreach ($section['summary'] ?? [] as $item) {
                $rows[] = [
                    $item['label'] ?? '',
                    $item['value'] ?? '',
                ];
            }

            if (!empty($section['table']['columns'])) {
                $rows[] = [];
                $rows[] = [$section['table']['title'] ?? 'Section Details'];
                $rows[] = $section['table']['columns'];

                foreach ($section['table']['rows'] ?? [] as $sectionRow) {
                    $rows[] = $sectionRow;
                }
            }
        }

        return $rows;
    }
}
