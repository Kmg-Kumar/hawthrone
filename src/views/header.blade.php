<section class="extension-header">
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link nav-link-alternative {{ Request::segment(3) == '' ? 'active' : null }}"
               href="{{url('plugin/hawthorne')}}">
                Home
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link nav-link-alternative {{ Request::segment(3) === 'mapping' ? 'active' : null }}"
               href="{{route('hawthorne.mapping')}}">
                Attribute Mapping
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link nav-link-alternative {{ Request::segment(3) === 'configuration' ? 'active' : null }}"
               href="{{route('hawthorne.config')}}">
                Configuration
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link nav-link-alternative {{ Request::segment(3) === 'log' ? 'active' : null }}"
               href="{{route('hawthorne.log')}}">
                Logs
            </a>
        </li>
    </ul>
</section>
