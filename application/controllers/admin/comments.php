<?php
class Admin_Comments_Controller extends Admin_Controller {
	public $restful = true;
	
	public function __construct() {
		parent::__construct();
		$this->filter("before", "csrf")->on("post")->only(array("edit", "delete"));
	}

	public function get_index() {
		$comments = Comment::all();
		return View::make("admin.comments.list", array("comments" => $comments, "title" => "Comments | Admin"));
	}

	// Edit
	public function get_edit($id) {
		$comment = Comment::find($id);
		if(!$comment) {
			Messages::add("error", "Comment not found");
			return Redirect::to_action("admin.comments");
		}
		return View::make("admin.comments.form", array("title" => "Edit ".e($comment->id)." | Comments | Admin", "comment" => $comment));
	}
	public function post_edit($id) {
		$comment = Comment::find($id);
		if(!$comment) {
			Messages::add("error", "Comment not found");
			return Redirect::to_action("admin.comments");
		}
		$validation_rules = array(
			"source"     => "required",
		);
		$input = Input::all();
		$validation = Validator::make($input, $validation_rules);
		if($validation->passes()) {
			$comment->source       = $input["source"];
			$changed = array_keys($comment->get_dirty());
			if($comment->save()) {
				Event::fire("admin", array("comment", "edit", $comment->id, $changed));
				Messages::add("success", "Comment updated!");
				return Redirect::to_action("admin.comments");
			} else {
				Messages::add("error", "Failed to save");
				return Redirect::to_action("admin.comments@edit")->with_input()->with_errors($validation);
			}
		} else {
			return Redirect::to_action("admin.comments@edit", array($id))->with_input()->with_errors($validation);
		}

	}

	// Delete form. DO NOT EVER DO ACTUAL DELETEION IN A GET METHOD
	public function get_delete($id) {
		$comment = Comment::find($id);
		if(!$comment) {
			Messages::add("error", "Comment not found");
			return Redirect::to_action("admin.comments");
		}
		return View::make("admin.comments.delete", array("title" => "Delete ".e($comment->id)." | Comments | Admin", "comment" => $comment));
	}
	// Deletion
	public function post_delete($id) {
		$comment = Comment::find($id);
		if(!$comment) {
			Messages::add("error", "Comment not found");
			return Redirect::to_action("admin.comments");
		}
		if ($comment->news_id != null) { //to update comment count if it's a news comment
			$comment->news->update_comment_count(true);
		}
		else if ($comment->map_id != null) { //to update comment count if it's a map comment
			$comment->map->update_comment_count(true);
		}
		$comment->user->update_comment_count(true);
		$comment->delete();
		Event::fire("admin", array("comment", "delete", $comment->id));
		Messages::add("success", "Comment deleted!");
		return Redirect::to_action("admin.comments");
	}
}
?>