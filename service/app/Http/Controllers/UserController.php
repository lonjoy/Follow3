<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\Star;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    private $user_id;

    public function __construct(Request $request)
    {
        $access_token = $request->input('access_token');
        if ( ! isset($access_token)) {
            abort(401, 'Need access token.');
        }
        $this->user_id = Cache::get('access_token:' . $access_token);
        if ( ! $this->user_id)
            abort(422123, 'Invalid access token.');
    }

    /**
     *
     * 个人用户信息
     *
     * @return mixed
     * @author: LuHao
     */
    public function self_profile()
    {
        $result = User::find($this->user_id);
        return $this->result($result);
    }

    /**
     *
     * 通用用户信息
     *
     * @param $user_id
     * @author: LuHao
     */
    public function profile($user_id)
    {
        $result = User::find($user_id); 
        if ($result) {
            return $this->result($result);
        } else {
            abort(922, 'No such user.');
        }
    }

    /**
     *
     * 关注主播
     *
     * @param $star_id
     * @return mixed
     * @author: LuHao
     */
    public function follow_star($star_id)
    {
        $star = Star::find($star_id);
        if ( ! $star)
            abort(2012, 'Wrong star id.');
        $f = Follow::isExist($this->user_id, $star_id)->first();
        if ($f)
            abort(23, 'Had been followed.');
        $follow = new Follow();
        $follow->star_id = $star->id;
        $follow->user_id = $this->user_id;
        $follow->is_notify = User::find($this->user_id)->is_auto_notify;
        $follow->save();
        $star->followers = $star->followers + 1;
        $star->save();
        return $this->result();
    }

    /**
     *
     * 取消关注主播
     *
     * @param $star_id
     * @return mixed
     * @author: LuHao
     */
    public function unfollow_star($star_id)
    {
        $f = Follow::isExist($this->user_id, $star_id)->first();
        if ( ! $f)
            abort('321', 'Not followed star.');
        $f->delete();
        $star = Star::find($star_id);
        $star->followers = $star->followers - 1;
        $star->save();
        return $this->result();
    }

    /**
     *
     * 已关注主播列表
     *
     * @return mixed
     * @author: LuHao
     */
    public function follow_list()
    {
        $data = Follow::where('user_id', $this->user_id)
            ->join('Star', 'Star.id', '=', 'Follow.star_id')
            ->orderBy('began_at', 'desc')
            ->get();
        return $this->result($data);
    }

    /**
     *
     * 列出所有关注且在线的主播
     *
     * @author: LuHao
     */
    public function online_list()
    {
        $result = Follow::where('user_id', $this->user_id)
            ->join('Star', 'Star.id', '=', 'Follow.star_id')
            ->orderBy('began_at', 'desc')
            ->where('Star.is_live', '=', true)
            ->get();
        return $this->result($result);
    }

    /**
     *
     * 热门主播
     *
     * @param $page
     * @author: LuHao
     */
    public function hot($page)
    {
        $result = Star::select('Star.id', 'Star.nickname', 'Star.title',
            'Star.cover', 'Star.link', 'Star.avatar', 'Star.platform',
            'Star.info', 'Star.serial', 'Star.followers', 'Star.is_live',
            'Star.began_at', 'Star.end_at', 'Follow.user_id')
            ->orderBy('Star.followers', 'desc')
            ->where('Star.is_live', true)
            ->limit(10)
            ->offset(10 * $page)
            ->leftJoin('Follow', function ($join) {
                $join->on('Star.id', '=', 'Follow.star_id')
                    ->where('Follow.user_id', '=', $this->user_id);
            })
            ->get();
        return $this->result($result);
    }

    /**
     *
     * 包含了是否关注信息的查询主播
     *
     * @param Request $request
     * @return mixed
     * @author: LuHao
     */
    public function search_star(Request $request)
    {
        $v = Validator::make($request->all(), [
            'query' => 'required'
        ]);
        if ($v->fails()) {
            abort(999, $v->errors());
        }
        $query = $request->input('query');
        $star = Star::select('Star.id', 'Star.nickname', 'Star.title',
            'Star.cover', 'Star.link', 'Star.avatar', 'Star.platform',
            'Star.info', 'Star.serial', 'Star.followers', 'Star.is_live',
            'Star.began_at', 'Star.end_at', 'Follow.user_id')->search($query)
            ->leftJoin('Follow', function ($join) {
                $join->on('Star.id', '=', 'Follow.star_id')
                    ->where('Follow.user_id', '=', $this->user_id);
            })->get();
        if ( ! $star->isEmpty()) {
            return $this->result($star);
        }
        abort(1000, 'No star found!');
    }

    /**
     *
     * 提交反馈
     *
     * @param Request $request
     * @return mixed
     * @author: LuHao
     */
    public function feedback(Request $request)
    {
        $v = Validator::make($request->all(), [
            'content' => 'required'
        ]);
        if ($v->fails()) {
            abort(999, $v->errors());
        }
        $content = $request->input('content');
        $feedback = new Feedback;
        $feedback->user_id = $this->user_id;
        $feedback->content = $content;
        $feedback->save();
        return $this->result();
    }

}
