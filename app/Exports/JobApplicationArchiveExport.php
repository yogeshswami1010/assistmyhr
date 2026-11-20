<?php

namespace App\Exports;
use App\JobApplication;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

class JobApplicationArchiveExport extends StringValueBinder implements FromQuery, WithMapping, WithHeadings, WithEvents, WithCustomValueBinder, ShouldAutoSize
{
    use Exportable;

    public function __construct(private Builder $candidates, private string $companyName)
    {
    }

    public function query(): Builder
    {
        return $this->candidates;
    }

    public function map($candidate): array
    {
        /** @var JobApplication $candidate */
        return [
            $candidate->id,
            $candidate->job?->title,
            $candidate->full_name,
            $candidate->email,
            $candidate->phone,
            $candidate->cover_letter,
            $candidate->trashed() ? 'Archived' : ($candidate->status?->status ?? ($candidate->is_candidate ? 'Internal' : 'No Status')),
            $candidate->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function headings(): array
    {
        return ['ID', 'Job Title', 'Name', 'Email', 'Mobile', 'Cover Letter', 'Status', 'Applied at'];
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport $event) {
                $event->writer->getProperties()->setTitle(__('menu.candidateDatabase'))->setDescription(__('modules.applicationArchive.exportFileDescription'))->setCreator('Recruit')->setCompany($this->companyName);
            },
            AfterSheet::class => function(AfterSheet $event) {
                $styleArray = [
                    'font' => [
                        'bold' => true,
                    ],
                ];
                $event->sheet->getDelegate()->getStyle('A1:H1')->applyFromArray($styleArray);
            },
        ];
    }
}
