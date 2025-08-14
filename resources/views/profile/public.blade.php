@extends('layouts.app')
@section('content')
    <div class="row">
    @include('layouts.components.sidebar')
        <div class="col-md-9">
        <h3 class="my-3">{{ $profile->display_name }}'s Public Profile</h3>
        <hr />
        <div class="row mt-2">
            <div class="col-md-9">
 

 
            </div>
        </div>
      </div>
    </div>
@endsection
