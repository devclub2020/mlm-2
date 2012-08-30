@layout("layout.admin")

@section("content")
@parent
<div id="content">
<div class="titlebar clearfix">
	<h2>Featuring map: <strong>{{ e($map->title) }}</strong></h2>
</div>
	{{ Form::open(null, 'POST', array('class' => 'xpadding')) }}
	{{ Form::token() }}
	<p>Are you sure you want to feature <strong>{{ e($map->title) }}</strong>?</p>
	{{ Form::submit("Feature", array("class" => "btn btn-danger")) }}
	{{ HTML::link_to_action("admin.maps", "Back", array(), array("class" => "btn")) }}
	{{ Form::close() }}
</div>
@endsection