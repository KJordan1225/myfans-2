@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">Connect Status Page</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
			

<div class="container py-4">
    <h1 class="h4 mb-3">Recent Webhooks</h1>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Account (Connect)</th>
                <th>Created</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($calls as $call)
                <tr>
                    <td>{{ $call->id }}</td>
                    <td><code>{{ $call->payload['type'] ?? 'n/a' }}</code></td>
                    <td>
                        <code>{{ $call->payload['account'] ?? 'platform' }}</code>
                    </td>
                    <td>{{ $call->created_at->toDayDateTimeString() }}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#payload-{{ $call->id }}">
                            Payload
                        </button>
                    </td>
                </tr>
                <tr class="collapse" id="payload-{{ $call->id }}">
                    <td colspan="5">
                        <pre class="mb-0 small">{{ json_encode($call->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection