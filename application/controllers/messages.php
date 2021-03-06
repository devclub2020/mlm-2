<?php
class Messages_Controller extends Base_Controller {
	public $restful = true;

	public function __construct() {
		parent::__construct();

		$this->filter("before", "auth"); // Require logged in user
		$this->filter("before", "csrf")->on("post")->only(array("new", "add", "reply"));
	}

	public function get_index() {
		$threads = Auth::user()->messages()->with("unread")->get();
		return View::make("messages.list", array("title" => "Messages", "threads" => $threads, "javascript" => array("profile", "messages")));
	}
	public function get_new($username = "") {
		$user = User::where_username($username)->first();
		$name = "";
		if (!$user && $username != "") //Check it's a real user
			Messages::add("error", "Couldn't find user!");
		else
			$name = $user->username; //Have to do this so that the view stuff doesn't derp out

		return View::make("messages.new", array("title" => "New Message", "javascript" => array("profile", "messages"), "name" => $name));
	}
	public function post_new() {
		$validation_rules = array(
			"title" => "required|min:4|max:255",
			"users" => "required",
			"message" => "required"
		);
		$validation = Validator::make(Input::all(), $validation_rules);
		$validation->passes(); // Generates the messages object

		// Check that all recipants exist and doesn't include the user
		$userlist = explode(",", Input::get("users"));
		$userlist = array_map(function($user) { // <3 PHP 5.3
			$user = strtolower(trim($user)); // Remove whitespace
			if($user == strtolower(Auth::user()->username) || strlen($user) == 0) { // No messaging yourself
				return;
			} else {
				return $user;
			}
		}, $userlist);
		// Remove empty entries
		$userlist = array_filter($userlist);
		// Find who actually exists
		if(count($userlist) > 0) {
			$userobjs = User::where_in("username", $userlist)->get();
			$foundusers = array_map(function($userobj) {
				return strtolower($userobj->username);
			}, $userobjs);
			
			$notfound = array_diff($userlist, $foundusers);
			if(count($notfound) > 0) {
				$validation->errors->add("users", "The following users couldn't found: ".e(implode(", ", $notfound)));
			}
		} else {
			$validation->errors->add("users", "No valid users to send to found");
		}

		if(count($validation->errors->messages) == 0) { // Equals $validation->passes(), only now also has properly checked users
			/* Insert the thread */
			$messageThread = Message_Thread::create(array("title" => Input::get("title"), "user_id" => Auth::user()->id));
			/* Attach users */
			foreach(array_merge(array(Auth::user()), $userobjs) as $userobj) {
				$messageThread->users()->attach($userobj->id, array("unread" => 1)); // Current user will mark it unread once opening the thread
			}
			/* Attach message */
			$message = new Message_Message();
			$message->user_id = Auth::user()->id;
			$message->message = Input::get("message");
			$messageThread->messages()->insert($message);
			Messages::add("success", "Message sent!");
			return Redirect::to_action("messages@view", array($messageThread->id));
		} else {
			Messages::add("error", "Errors occured when trying to send the message");
			return Redirect::to_action("messages@new")->with_input()->with_errors($validation);
		}
	}
	/* Viewing thread */
	public function get_view($threadid) {
		$thread = Auth::user()->messages()->where_message_thread_id($threadid)->with("unread")->first(); // Makes sure it's readable by the user
		if(!$thread) {
			return Response::error('404');
		}
		if($thread->pivot->unread) {
			// Since pivot table handing is bad in laravel, doing it manually.
			// It's same as following:
			// $thread->pivot->unread = false;
			// $thread->pivot->save();
			DB::table("message_users")->where_message_thread_id($thread->id)->where_user_id(Auth::user()->id)->update(array("unread" => 0));
		}
		$messages = Message_Message::with("user")->where_message_thread_id($thread->id)->get();
		return View::make("messages.view", array("title" => e($thread->title)." | Messages", "thread" => $thread, "messages" => $messages, "javascript" => array("profile", "messages")));
	}
	/* Replying to thread */
	public function post_reply($threadid) {
		$thread = Auth::user()->messages()->where_message_thread_id($threadid)->first(); // Makes sure it's readable by the user
		if(!$thread) {
			return Response::error('404');
		}
		$validation_rules = array(
			"message" => "required"
		);
		$validation = Validator::make(Input::all(), $validation_rules);
		if($validation->passes()) {
			$message = new Message_Message();
			$message->user_id = Auth::user()->id;
			$message->message = Input::get("message");
			$thread->messages()->insert($message);

			Messages::add("success", "Reply sent!");
			return Redirect::to_action("messages@view", array($threadid));
		} else {
			Messages::add("error", "Message not sent!");
			return Redirect::to_action("messages@view", array($threadid))->with_input()->with_errors($validation);
		}
	}
	/* Adding more people to thread */
	public function post_add($threadid) {
		$thread = Auth::user()->messages()->where_message_thread_id($threadid)->first(); // Makes sure it's readable by the user
		if(!$thread) {
			return Response::error('404');
		}
		if(!$thread->user_id) { // If thread was started by the system
			return Response::error('404');
		}
		$validation_rules = array(
			"users" => "required"
		);
		$validation = Validator::make(Input::all(), $validation_rules);
		$validation->passes(); // Generates the messages object

		// Check that all recipants exist and doesn't include current users
		$current_users = array();
		foreach ($thread->users as $user) {
			$current_users[] = strtolower($user->username);
		}
		$userlist = explode(",", Input::get("users"));
		$userlist = array_map(function($user) use($current_users) { // <3 PHP 5.3
			$user = strtolower(trim($user)); // Remove whitespace
			if(in_array($user, $current_users) || strlen($user) == 0) { // No messaging yourself
				return;
			} else {
				return $user;
			}
		}, $userlist);
		// Remove empty entries
		$userlist = array_filter($userlist);
		// Find who actually exists
		if(count($userlist) > 0) {
			$userobjs = User::where_in("username", $userlist)->get();
			$foundusers = array_map(function($userobj) {
				return strtolower($userobj->username);
			}, $userobjs);
			
			$notfound = array_diff($userlist, $foundusers);
			if(count($notfound) > 0) {
				$validation->errors->add("users", "The following users couldn't found: ".e(implode(", ", $notfound)));
			}
		} else {
			$validation->errors->add("users", "No valid users to send to found (Possible that everyone listed is already in this thread)");
		}
		if(count($validation->errors->messages) == 0) { // Equals $validation->passes(), only now also has properly checked users
			/* Attach users & write a nice message */
			$newusertxt = "Added people to this thread:\n\n";
			foreach($userobjs as $userobj) {
				$thread->users()->attach($userobj->id, array("unread" => 1));
				$newusertxt .= "* [![Pavatar](http://minotar.net/helm/{$userobj->mc_username}/15.png) {$userobj->username}](http://mlm.dev/user/{$userobj->username})\n";
			}
			/* Attach message */

			$message = new Message_Message();
			$message->message = $newusertxt;
			$thread->messages()->insert($message);
			Messages::add("success", "Users added!");
			return Redirect::to_action("messages@view", array($thread->id));
		} else {
			Messages::add("error", "Problems occued when adding users");
			return Redirect::to_action("messages@view", array($thread->id))->with_input()->with_errors($validation);
		}
	}
}