@extends('layouts.app')

@section('content')
<div class="row">
    @include('layouts.components.sidebar')
    <div class="col-md-9">
        <h3 class="my-3">{{ auth()->user()->profile->display_name }}'s Posts</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">

                <div class="container mt-4">
                    <h2 class="mb-4">All Posts</h2>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if($posts->isEmpty())
                        <div class="alert alert-info text-center">
                            No posts found for this creator.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle" id="postsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($posts as $post)
                                        <tr>
                                            <td>{{ $post->id }}</td>
                                            <td>{{ $post->title }}</td>
                                            <td class="text-center">
                                                <a href="#" class="btn btn-sm btn-warning me-1">Edit</a>

                                                <form action="#" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger me-1">Delete</button>
                                                </form>

                                                <a href="#" class="btn btn-sm btn-info">Media</a>
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
@endsection


@push('scripts')
<!-- Optionally include jQuery & DataTables -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#postsTable').DataTable({
            "columnDefs": [
                { "orderable": false, "targets": 2 }
            ]
        });
    });
</script>
@endpush