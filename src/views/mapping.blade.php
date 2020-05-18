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
                {!! Form::open(array('url' => route('hawthorne.storeAttribute'),'method'=>'POST','id'=>'map_form')) !!}
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div class="row">
                            <div class="col">
                                <h3>Attribute Mapping</h3>
                            </div>
                            <div class="col">
                                <button type="submit"
                                        class="btn btn-success">
                                    Save
                                </button>
                                <a href="{{route('hawthorne.sync')}}" class="btn btn-success"> Attribute Sync </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-12 content">
                                <div class="row item-row">
                                    <div class="col-xl-12">
                                        <div class="row">
                                            <div class="col-xl-4 col-lg-6">
                                                <h4>{{__('flexiPIM Attribute')}}</h4>
                                            </div>
                                            <div class="col-xl-4 col-lg-6">
                                                <h4>{{__('Uploaded File Attribute')}}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @foreach($flexiPIMAttribute as $key => $header)
                                    <div class="row item-row">
                                        <div class="col-xl-12">
                                            <div class="row">
                                                <div class="col-xl-4 col-lg-6">
                                                    {!! Form::text('flexipim_attribute', $header , array('value' => $key,'class' => 'form-control mb-3',"id"=>"flexiPimHeaderOptions",'disabled')) !!}
                                                </div>
                                                <div class="col-xl-4 col-lg-6">
                                                    {!! Form::select($header, $hawthorneAttribute,isset($mappedAttribute[$key]) ? $mappedAttribute[$key] : null, array('name'=>"mapped_attribute[$key]",'class' => 'form-control custom-select single-select mapping__single-select mb-3 ',"id"=>"uploadedFileHeaderOptions")) !!}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </section>
@endsection
