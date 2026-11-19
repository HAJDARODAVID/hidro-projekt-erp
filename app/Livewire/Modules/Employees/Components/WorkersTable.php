<?php

namespace App\Livewire\Modules\Employees\Components;

use App\Models\WorkerAddress;
use App\Models\Employees\Worker;
use App\Models\Employees\WorkerStatus;
use App\Models\Employees\WorkerType;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Livewire\Tables\AppDataTable;

class WorkersTable extends AppDataTable
{
    protected $model = Worker::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        // $this->setPerPageAccepted([15, 25, 50, 100]);
        // $this->setPerPage(15);

        // our buttons on the right of the toolbar (icon only, the text is the tooltip)
        $this->toolbarButtons([
            [
                'icon'  => 'person-plus',
                'title' => translator('New worker'),
                'type'  => 'suc',
                'modal' => 'create-worker',
                'can'   => CreateWorkerModal::PERMISSION,
            ],
        ], 'toolbar-right-end');

        $this->setTableRowUrl(function ($row) {
            return route('getWorkerInfo', ['worker' => $row->id]);
        });

        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if ($column->getTitle() == "") {
                return [
                    'style' => 'width: 20px !Important',
                ];
            } elseif ($column->isField('id')) {

                return [
                    'style' => 'width: 20px !Important',
                ];
            }
            return [];
        });
    }

    public function columns(): array
    {
        return [
            Column::make("#", "id"),
            Column::make("IME", "firstName")
                ->sortable()
                ->searchable(),
            Column::make("PREZIME", "lastName")
                ->sortable()
                ->searchable(),
            Column::make("OIB", "OIB")
                ->searchable(),
            Column::make("ADRESA", "ced")
                ->label(fn($row, Column $column) => $this->getWorkerAddress($row)),
            Column::make("RADNO MJESTO", "working_place")
                ->searchable(),
            $this->dateColumn("DATUM ZAPOŠLJENJA", "doe"),
            $this->dateColumn("UGOVOR TRAJE DO", "ced"),
            Column::make("", "actions")
                ->label(
                    fn($row, Column $column) => view('components.table.table-actions', ['row' => $row, 'livewire' => 'modules.employees.components.table-actions'])
                )
                ->unclickable(),
        ];
    }

    private function getWorkerAddress($row)
    {
        $address = WorkerAddress::where('worker_id', $row->id)->first();
        if ($address) return $address->street . ', ' . $address->town . ' ' . $address->zip;
        return NULL;
    }

    public function builder(): Builder
    {
        return Worker::query()
            ->where('status', WorkerStatus::WORKER_STATUS_ACTIVE)
            ->whereIn('type', WorkerType::init()->getAllType());
    }
}
