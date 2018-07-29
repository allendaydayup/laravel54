<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;
use App\Comment;
use App\Zan;

class PostController extends Controller
{
    //列表页
    public function index()
    {

        $return['posts'] = Post::orderBy('created_at', 'desc')->withCount(['comments', 'zans'])->paginate(5);


        return view('post/index', $return);
    }

    //详情页
    public function show(Post $post)
    {

        $post->load('comments');
        $return = [
            'post' => $post,
        ];
        return view('post/show', $return);
    }

    //创建页
    public function create()
    {
        return view('post/create');
    }

    //创建逻辑
    public function store()
    {
        //验证
        $this->validate(request(), [
            'title' => 'required|string|max:100|min:5',
            'content' => 'required|string|min:10',
        ]);

        //逻辑
        $user_id = \Auth::id();
        $params = array_merge(request(['title', 'content']), compact('user_id'));
        $post = Post::create($params);

        //渲染
        return redirect('/posts');

    }

    //编辑页
    public function edit(Post $post)
    {


        $return = [
            'post' => $post,
        ];
        return view('post/edit', $return);
    }

    //编辑逻辑
    public function update(Post $post)
    {
        //验证
        $this->validate(request(), [
            'title' => 'required|string|max:100|min:5',
            'content' => 'required|string|min:10',
        ]);

        $this->authorize('update', $post);

        //逻辑
        $post->title = request('title');
        $post->content = request('content');
        $post->save();

        //渲染
        return redirect("posts/{$post->id}");
    }

    //删除文章
    public function delete(Post $post)
    {
        //权限验证
        $this->authorize('delete', $post);

        $post->delete();
        return redirect('/posts');
    }

    //图片上传
    public function imageUpload(Request $request)
    {
        $path = $request->file('wangEditorH5File')->storePublicly(md5(time()));
        return asset('storage/'.$path);
    }

    //提交评论
    public function comment(Post $post)
    {
        $this->validate(request(), [
            'content' => 'required|min:3'
        ]);


        $comment = new Comment();
        $comment->user_id = \Auth::id();
        $comment->content = request('content');
        $post->comments()->save($comment);


        return back();
    }

    public function zan(Post $post)
    {
        $param = [
            'user_id' => \Auth::id(),
            'post_id' => $post->id,
        ];

        Zan::firstOrCreate($param);

        return back();
    }


    public function unzan(Post $post)
    {
        $post->zan(\Auth::id())->delete();
        return back();
    }

    //搜索结果页
    public function search()
    {
        $this->validate(request(), [
            'query' => 'required',
        ]);

        $query = request('query');
        $posts = \App\Post::search($query)->paginate(2);

        return view("post/search", compact('posts', 'query'));
    }
}
