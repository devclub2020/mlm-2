@layout("layout.main")

@section('content')

<div class="content" id="news">
	@foreach($newslist->results as $article)
		<h3>{{ HTML::link_to_action("news@view", $article->title, array($article->id, $article->slug)) }}</h3>
		{{ HTML::image($article->image->file_small, "Image") }}
		{{ nl2br(e($article->summary)) }}
	@endforeach
	{{ $newslist->links() }}
</div>
@endsection