@extends('layouts.app')
@section('content')
    <section class="extension">
        <!-- HydroFarm Header -->
        <div class="row">
            <div class="col-xl-12">
                @include('Hawthorne::header')
            </div>
        </div>

        <!-- HydroFarm Content -->
        <div class="card shadow-lg">
            {!! Form::open(array('route' => 'hawthorne.configSave','method'=>'POST','files'=> true,'name'=>'hawthorneConfigSave','id'=>'hawthorneConfigSave')) !!}

            <div class="card-header bg-secondary">
                <div class="row align-items-center">
                    <div class="col-6">
                        <h3>Configuration</h3>
                    </div>
                    <div class="col-6 text-right">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @if (session('success'))
                        <div class="col-xl-12">
                            <div class="alert alert-success">
                                {!! session('success') !!}
                            </div>
                        </div>
                    @endif
                    @if (session('failed'))
                        <div class="col-xl-12">
                            <div class="alert alert-warning">
                                {!! session('failed') !!}
                            </div>
                        </div>
                    @endif

                    <div class="col-xl-12">
                        <h6 class="heading-small text-muted mb-4">API Configuration</h6>
                        <hr/>

                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">Token Name</label>
                                        <input type="text"
                                               value="Hawthorne Access Details"
                                               class="form-control form-control-alternative"
                                               name="token_name"
                                               readonly
                                               required>
                                    </div>
                                </div>


                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">Access Token URL <span
                                                class="error">*</span></label>
                                        {!! Form::text('access_url', isset($config_data->access_url) ? $config_data->access_url : '', array('placeholder' => 'ex: http://p21.client.com/connect/token','class' => 'form-control form-control-alternative')) !!}
                                        @if ($errors->has('access_url'))
                                            <div
                                                class="validation-alert">{{ $errors->first('access_url') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">Client ID <span
                                                class="error">*</span></label>
                                        {!! Form::text('client_key', isset($config_data->client_key) ? $config_data->client_key : '', array('class' => 'form-control form-control-alternative')) !!}
                                        @if ($errors->has('client_key'))
                                            <div class="validation-alert">{{ $errors->first('client_key') }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">Client Secret <span
                                                class="error">*</span></label>
                                        {!! Form::text('secret_key', isset($config_data->secret_key) ? $config_data->secret_key : '', array('class' => 'form-control form-control-alternative')) !!}
                                        @if ($errors->has('secret_key'))
                                            <div
                                                class="validation-alert">{{ $errors->first('secret_key') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mb-4">API URL Configuration</h6>
                        <hr/>

                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">Product API URL <span
                                                class="error">*</span></label>
                                        {!! Form::text('products_access_url', isset($config_data->products_access_url) ? $config_data->products_access_url : '', array('class' => 'form-control')) !!}
                                        @if ($errors->has('products_access_url'))
                                            <div
                                                class="validation-alert">{{ $errors->first('products_access_url') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mb-4">Cron Setting</h6>
                        <hr/>

                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">Cron Sync Time</label>
                                        {!! Form::select('cron_time',$cronOption,isset($config_data->cron_time) ? $config_data->cron_time : '', array('class' => 'form-control custom-select single-select')) !!}
                                        @if ($errors->has('cron_time'))
                                            <div class="validation-alert">{{ $errors->first('cron_time') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h6 class="heading-small text-muted mb-4">flexiPIM Setting</h6>
                        <hr/>

                        <div class="pl-lg-4">
                            <div class="row">
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">flexiPIM Default Channel <span
                                                class="error">*</span></label>
                                        {!! Form::select('channel_id',$channelList,isset($config_data->channel_id) ? $config_data->channel_id : '', array('class' => 'form-control custom-select single-select')) !!}
                                        @if ($errors->has('channel_id'))
                                            <div class="validation-alert">{{ $errors->first('channel_id') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">flexiPIM Default Family <span
                                                class="error">*</span></label>
                                        {!! Form::select('family_id',$familyList,isset($config_data->family_id) ? $config_data->family_id : '', array('class' => 'form-control custom-select single-select')) !!}
                                        @if ($errors->has('family_id'))
                                            <div class="validation-alert">{{ $errors->first('family_id') }}</div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-xl-4 col-lg-6">
                                    <div class="form-group">
                                        <label class="form-control-label">flexiPIM Default Category <span
                                                class="error">*</span></label>
                                        {!! Form::select('category_id',$categoryList,isset($config_data->category_id) ? $config_data->category_id : '', array('class' => 'form-control custom-select multi-select','multiple')) !!}
                                        @if ($errors->has('category_id'))
                                            <div class="validation-alert">{{ $errors->first('category_id') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {!! Form::close() !!}
            </div>
        </div>
    </section>
@endsection
