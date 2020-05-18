@extends('layouts.app')
@section('content')
    <section class="extension">
        <div class="row">
            <!-- Hydrofarm Header -->
            <div class="col-xl-12">
                @include('Hawthorne::header')
            </div>

            <!-- Hydrofarm Content -->
            <div class="col-xl-4 mb-4">
                <div class="card">
                    <img src="{{$extensionData->extension_logo}}"
                         class="card-img-top">
                    <div class="card-body">
                        <h2 class="card-title mb-0">{{$extensionData->extension_name}}</h2>
                        <small class="text-muted">{{$extensionData->description}}</small>
                        <div>
                            <a class="btn btn-success mt-3"
                               href="{{route('hawthorne.syncProduct')}}">
                                Manual Product Sync
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title text-muted">Last Data Sync Date</h4>
                                        <span
                                            class="h2 font-weight-bold mb-0">
                                            {{isset($configData->last_sync_date) ? getUserTimeZoneDate($configData->last_sync_date,'m/d/Y H:i:s') : ''}}
                                        </span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-gradient-red text-white rounded-circle shadow">
                                            <i class="fas fa-calendar-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title text-muted">
                                            Mapped Attribute Count
                                        </h4>
                                        <span class="h2 font-weight-bold mb-0">{{$mappedAttributeCount}}</span>
                                    </div>
                                    <div class="col-auto">
                                        <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                                            <i class="ni ni-tag"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title text-muted">
                                            HydroFarm Attribute Count
                                        </h4>
                                        <span class="h2 font-weight-bold mb-0">{{$hawthorneAttribute}}</span>
                                    </div>
                                    <div class="col-auto">
                                        <div
                                            class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                                            <i class="ni ni-tag"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card card-stats">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col">
                                        <h4 class="card-title text-muted">Scheduler Frequency</h4>
                                        <span class="h2 font-weight-bold mb-0">
                                            {{isset($configData) ? config('constants.cron_option')[$configData->cron_time] : ''}}
                                        </span>
                                    </div>
                                    <div class="col-auto">
                                        <div
                                            class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

