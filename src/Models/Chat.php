<?php

namespace Frameworkteam\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class Chat extends Model
{
    protected $table = 'im__chat';

    protected $fillable = [
        'user_id',
        'chat_id'
    ];

    public function user()
    {
        return $this->hasOne(config('Chat')['User'], 'id', 'user_id');
    }

    public function message()
    {
        return $this->hasMany('Frameworkteam\Chat\Models\Message', 'chat_id', 'chat_id');
    }

    public static function getByUser($id, $second_id)
    {
        $chat = Chat::where('user_id','=', $id)->get();
        foreach($chat as $c){
            $ch = Chat::where('chat_id','=', $c->chat_id)->where('user_id', '<>', $id)->first();
            if($ch->user_id == $second_id){
                return $ch->chat_id;
            }
        }
        return false;
    }

    public static function countParticipant($chat){
        return  Chat::where('chat_id','=',$chat)->count();
    }

    public static function create_($id_current, $id_second){
        $hash = Str::random(70);
        Chat::create(array('user_id'=>$id_current, 'chat_id'=>$hash));
        Chat::create(array('user_id'=>$id_second, 'chat_id'=>$hash));
        return $hash;
    }

    public static function allChats(){
        $chats = Chat::where('user_id', '=', Auth::id())->get();
        $result = array();
        foreach($chats as $chat){
            $users = self::getUsers($chat->chat_id);
            unset($users[Auth::id()]);
            $chat->new_messages_count = self::countNewMessages($chat->chat_id);
            $result[] = (object)array('users'=>$users, 'data'=>$chat);
        }
        return $result;
    }

    public static function countNewMessages($chat){
        return Message::where('chat_id','=', $chat)->where('is_read', '=', '0')->count();
    }

    public static function getUsers($chat){
        $user = array();
        foreach( Chat::where('chat_id','=',$chat)->get() as $chat_line){
            $user[$chat_line->user_id] = User::find($chat_line->user_id);
        }
        return $user;
    }

    public static function addUser($chat, $id){
        return Chat::create(array('user_id'=>$id, 'chat_id'=>$chat));
    }

// function below added by superjarilo
    public static function getChats($userId, $limit = null)
    {
        $chats = null;

        if ($userId) {
            $query = self::select('im__chats.*')
                ->leftJoin(DB::raw('im__chats as ic'), 'ic.chat_id', '=', 'im__chats.chat_id')
                ->where('ic.user_id', $userId)->where('im__chats.user_id', '<>', $userId)
                ->orderBy('im__chats.updated_at', 'desc')
                ->with('user.image');
            if ($limit) {
                $query->limit($limit);
            }
        }

        return $query->get();
    }


    public static function getChatsCount($chats, $userId)
    {
        $chatsCount = null;

        if (count($chats) && $userId) {
            $qb = DB::table('im__messages')->select('chat_id', DB::raw('count(*) as count'))
                ->whereIn('chat_id', $chats->lists('chat_id'))
                ->where('from_id', '<>', $userId)
                ->where('is_read', 0)
                ->whereNull('deleted_at')
                ->groupBy('chat_id');

            $chatsCount = collect($qb->get())->lists('count', 'chat_id');
        }

        return $chatsCount;
    }

    public function config() {
        dd(config());
    }
}