{{-- "Renew" button of a certificate / check: opens the global modal renew-certificate. $certificateId --}}
<button type="button" class="btn btn-light gray-border-color btn-sm shadow" style="border-radius: 0px!important;"
    title="{{ translator('Renew') }}"
    data-trigger="global-modal" data-component="renew-certificate" data-params='@json(['certificateId' => (int) $certificateId])'>
    <div class="d-flex align-items-center gap-2"><i class="bi bi-arrow-repeat"></i></div>
</button>
