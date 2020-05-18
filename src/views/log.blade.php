@extends('layouts.app')
@section('content')
    <section class="extension">
        <div class="row">
            <!-- HydroFarm Header -->
            <div class="col-xl-12">
                @include('Hawthorne::header')
            </div>
            <!-- HydroFarm Content -->
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="row">
                            <div class="col">
                                <h3>Logs</h3>
                            </div>
                            <div class="col">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-12">
                                <table class="table">
                                    <thead class="thead-light">
                                    <tr>
                                        <th scope="col">Sync Date & Time</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Created By</th>
                                        <th scope="col">Log File</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($logList as $value)
                                        <tr>
                                            <td>{{getUserTimeZoneDate($value->created_at,'m/d/Y H:i:s')}}</td>
                                            <td><span class="badge badge-dot">
                                                        <i class="{{$value->status == 'success' ? 'bg-success' : ($value->status == 'failed' ? 'bg-danger' : 'bg-info') }}"></i>
                                                            <span class="status"></span>
                                                    </span>{{strtoupper($value->status)}}</td>
                                            <td>{{$value->username}}</td>
                                            <td>
                                                <a href="{{url('plugin/hawthorne/download_log',$value->file_name)}}">
                                                    {{$value->file_name}}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
