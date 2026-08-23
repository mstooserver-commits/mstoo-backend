<footer class="footer mt-auto">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 d-flex justify-content-center justify-content-md-start mb-2 mb-md-0">
                {{(business_config('footer_text','business_information'))->live_values??"MSTOO Admin"}} <span class="currentYear ms-2"></span>
            </div>
            <div class="col-md-6 d-flex justify-content-center justify-content-md-end">
                <ul class="list-inline list-separator">
                    <li>
                        <a href="{{route('admin.profile_update')}}">{{translate('profile')}}</a>
                    </li>
                    <li>
                        <a href="{{route('admin.dashboard')}}">
                            <span class="material-icons">home</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
