@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">My Subscribers</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			
			
			
			

      <div class="card shadow-sm">
        <div class="card-body">
          @if($subscribers->isEmpty())
            <p class="text-muted mb-0">No active subscribers yet.</p>
          @else
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Subscriber</th>
                    <th>Display</th>
                    <th>Since</th>
                    <th>Status</th>
                    <th>Ref</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($subscribers as $user)
                    @php $pivot = $user->pivot ?? null; @endphp
                    <tr>
                      <td>
                        <div class="fw-semibold">{{ $user->name }}</div>
                        <div class="text-muted small">{{ $user->email }}</div>
                      </td>
                      <td>{{ $user->creatorProfile?->display_name ?? '—' }}</td>
                      <td>
                        {{ $pivot?->starts_at ? \Illuminate\Support\Carbon::parse($pivot->starts_at)->toFormattedDateString() : '—' }}
                      </td>
                      <td>
                        @php
                          $active = ($pivot?->is_active && $pivot?->status === 'active');
                          $badge  = $active ? 'bg-success' : 'bg-secondary';
                        @endphp
                        <span class="badge {{ $badge }}">{{ $pivot?->status ?? 'unknown' }}</span>
                      </td>
                      <td class="small text-muted">
                        @if(!empty($pivot?->provider_subscription_id))
                          #{{ \Illuminate\Support\Str::limit($pivot->provider_subscription_id, 12) }}
                        @else
                          —
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>

    </div>
  </div>
</div>

{{-- SweetAlert2 Toasts --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, timerProgressBar: true });

  @if(session('success'))
    Toast.fire({ icon: 'success', title: @json(session('success')) });
  @endif
  @if(session('error'))
    Toast.fire({ icon: 'error', title: @json(session('error')) });
  @endif
  @if ($errors->any())
    Toast.fire({ icon: 'error', title: @json($errors->first()) });
  @endif
});
</script>




            </div>
        </div>
      </div>
    </div>
@endsection