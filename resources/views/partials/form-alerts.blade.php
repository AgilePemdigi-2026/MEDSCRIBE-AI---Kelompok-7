@if (session('status'))
    <div class="alert alert-ok mb-5" role="status">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
        <span><span class="font-semibold">Informasi:</span> <span class="text-text-body">{{ session('status') }}</span></span>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mb-5" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
        <div class="space-y-0.5">
            @foreach ($errors->all() as $error)
                <p class="font-medium">{{ $error }}</p>
            @endforeach
        </div>
    </div>
@endif
