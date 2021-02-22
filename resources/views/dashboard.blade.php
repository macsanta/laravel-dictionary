
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12 ">
            <div class="panel panel-default">
                <div class="panel-heading">Dictionary</div>

                <div class="panel-body" id="dictionary">
                    <dictionary></dictionary>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var dictionaryRouteName = '{{ config('dictionary.route')}}';
</script>
<script src="https://cdn.jsdelivr.net/npm/vue"></script>
<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<script src="{{ asset('vendor/dictionary/dictionary.js') }}"></script>
@endsection
