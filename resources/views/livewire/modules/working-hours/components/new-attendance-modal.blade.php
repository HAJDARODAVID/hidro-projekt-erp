<div>
    <x-ui.modal.modal-open-btn />

     <x-ui.modal :modalStatus=$modalStatus  size="l" >
        <x-slot:title>{{strtoupper(translator("Attendance work diary")) }}</x-slot:title>
        <x-ui.card :noBodyPadding=TRUE loading="diaryInfo" :border=FALSE loading="removeWorkDiaryFromAttendanceAction">
            <div class="d-flex gap-2 m-2" wire:key="container-{{ now() }}">
                testis
            </div>
        </x-ui.card>
    </x-ui.modal>
</div>
