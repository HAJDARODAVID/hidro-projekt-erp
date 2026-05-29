<div class="modal helvetica modal-bg-blur" id="calendar-modal" style="display: @isset($modalStatus)@if ($modalStatus) block @else none @endif @endisset;" wire:click='closeModal'>
    <div class="modal-dialog" wire:click.stop>
        <div class="modal-content" style="border-radius: 0px">
            <div class="container p-0" wire:key="calendar-container-{{ now() }}">
                <div class="calendar-container shadow no-border-radius">
                    <div class="calendar-header d-flex justify-content-between align-items-center">
                        <div class="">
                            <h2 class="mb-0">April 2026</h2>
                            Subtext here
                        </div>
                        <div>
                            <div class="d-flex gap-1">
                                <x-ui.btn type="lig.sm" >&lt;</x-ui.btn>
                                <x-ui.btn type="lig.sm" >{{ translator("Today") }}</x-ui.btn>
                                <x-ui.btn type="lig.sm" >&gt;</x-ui.btn>
                            </div>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <div class="day-name">{{ translator("Mon") }}</div>
                        <div class="day-name">{{ translator("Tue") }}</div>
                        <div class="day-name">{{ translator("Wed") }}</div>
                        <div class="day-name">{{ translator("Thu") }}</div>
                        <div class="day-name">{{ translator("Fri") }}</div>
                        <div class="day-name">{{ translator("Sat") }}</div>
                        <div class="day-name">{{ translator("Sun") }}</div>
                    </div>

                    <div class="calendar-grid">
                        @foreach ($calendarDates as $cw => $cwDate)
                            @foreach ($cwDate as $date)
                                <x-ui.calendar.calendar-day :info="array_merge($date, ['setMonth' => $month, 'selectedDate' => $selectedDate])"/>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>