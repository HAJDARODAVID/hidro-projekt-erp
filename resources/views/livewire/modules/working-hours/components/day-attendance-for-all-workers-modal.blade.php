<div>
    <div class="text-muted small mb-2">{{ translator('Date') }}: {{ date('Y-m-d', $date) }}</div>
    <x-ui.card :noBodyPadding=TRUE loading="applyAbsenceAction, deleteAllAttendanceAction" :border=FALSE>
        <div class="d-flex justify-content-center gap-3">
            @foreach ($absenceType as $typeCode => $typeData)
                <x-ui.btn type="dar.lg" text="{{ translator($typeData['short-text']) }}" wClickMethod="applyAbsenceAction" wClickParam="{{ $typeCode }}" />
            @endforeach
            @if($showDeleteAtt)
                <x-v-divider px=0 />
                <x-ui.btn type="dan.lg" icon="trash" wClickMethod="deleteAllAttendanceAction" />
            @endif
        </div>

    </x-ui.card>
</div>