<?php

namespace App\Http\Controllers\Api;

use App\Models\Chatting;
use App\Models\Friend;
use App\Traits\ApiResponser;
use App\Traits\MessageLanguage;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ChattingController extends Controller
{
    use ApiResponser, MessageLanguage;

    public function playerChatting(Request $request)
    {
        $this->checkLang($request);

        $validator = Validator::make($request->all(), [
            'player_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            $errors = collect([]);
            foreach ($validator->messages()->all() as $item) {
                $errors->push($item);
            }
            return $this->apiResponse(null, $errors, 422, 0);
        }
        $messages = Chatting::with(['sender:id,first_name', 'receiver:id,first_name'])
            ->where('sender_id', $request->player_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('receiver_id');
        return $this->apiResponse($messages, null, 200, 1);
    }

    public function chatDetails(Request $request)
    {
        $this->checkLang($request);
        $receiver_id = $request->header('receiver_id');
        $sender_id = $request->header('sender_id');

        $messages = Chatting::where('sender_id', $sender_id)
            ->Where('receiver_id', $receiver_id)
            ->orWhere('receiver_id', $sender_id)
            ->where('sender_id', $receiver_id)
            ->orderBy('created_at', 'ASC')
            ->paginate(10);

        // Change status to seen if the receiver see the message only
        foreach ($messages as $message) {
            $message->receiver_id == auth()->guard('player')->user()->id && $message->status == 0 ? Chatting::where('id', $message->id)->update(['status' => 1]) : '';
        }
        return $messages;

    }

    public function sendMessage(Request $request)
    {
        $this->checkLang($request);
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
            'message' => 'required',
            'content_type' => 'required',
        ]);

        if ($validator->fails()) {
            $errors = collect([]);
            foreach ($validator->messages()->all() as $item) {
                $errors->push($item);
            }
            return $this->apiResponse(null, $errors, 422, 0);
        }

        Chatting::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $request->receiver_id,
            'content' => $request->message,
            'content_type' => $request->content_type,
            'status' => 0
        ]);
        switch ($request->header('lang')) {
            case 'en':
                $message = 'Message sent successfully';
                break;
            case 'ar':
                $message = "تم إرسال الرسالة بنجاح";
                break;
            default:
                $message = 'Message sent successfully';
                break;
        }
        return $this->apiResponse(null, $message, 200, 1);
    }

    public function friends(Request $request)
    {

        $this->checkLang($request);
        $player_id = auth()->user()->id;
        $friends = Friend::with('friends:id,first_name,last_name')
            ->where('player_id', $player_id)
            ->where('status', 1)
            ->get()
            ->pluck(['friends']);

        foreach ($friends as $friend) {

            $friend['online'] = $this->isOnline($friend->id);

        }


        return $this->apiResponse($friends, null, 200, 1);
    }

    public function isOnline($id)
    {
        return Cache::has('user-is-online-' . $id);
    }

    public function sendFriendRequest(Request $request)
    {
        $this->checkLang($request);

        $player = Friend::where('player_id', $request->user()->id)->where('friend_id', $request->friend_id)->first();

        if ($player != null) {
            if ($player->status == Friend::APPROVED) {
                switch ($request->header('lang')) {
                    case 'en':
                        $message = 'You were already friends';
                        break;
                    case 'ar':
                        $message = " أنتما صديقان بالفعل";
                        break;
                    default:
                        $message = 'You were already friends';
                        break;
                }

            } else {
                $player->update(['status' => Friend::REQUEST_FRIEND]);

                switch ($request->header('lang')) {
                    case 'en':
                        $message = 'Request sent successfully';
                        break;
                    case 'ar':
                        $message = "تم إرسال الطلب بنجاح";
                        break;
                    default:
                        $message = 'Request sent successfully';
                        break;
                }
            }
            return $this->apiResponse(null, $message, 200, 0);
        } else {
            try {

                Friend::create([
                    'player_id' => $request->user()->id,
                    'friend_id' => $request->friend_id,
                    'status' => Friend::REQUEST_FRIEND,
                ]);

            } catch (QueryException $exception) {
                switch ($request->header('lang')) {
                    case 'en':
                        $message = 'Something went wrong';
                        break;
                    case 'ar':
                        $message = 'هناك خطأ ما';
                        break;
                    default:
                        $message = 'Something went wrong';
                        break;
                }
                return $this->apiResponse(null, $message, 200, 0);
            }
            switch ($request->header('lang')) {
                case 'en':
                    $message = 'Request sent successfully';
                    break;
                case 'ar':
                    $message = "تم إرسال الطلب بنجاح";
                    break;
                default:
                    $message = 'Request sent successfully';
                    break;
            }
            return $this->apiResponse(null, $message, 200, 1);
        }
    }

    public function friendRequest(Request $request)
    {
        $this->checkLang($request);

        $requestNew['new'] = Friend::where('player_id', $request->user()->id)->where('status', Friend::REQUEST_FRIEND)->get();

        $requestPending['pending'] = Friend::where('player_id', $request->user()->id)->where('status', Friend::SEEN_AND_PENDING)->get();

        Friend::where('player_id', $request->user()->id)
            ->where('status', Friend::REQUEST_FRIEND)
            ->update(['status' => Friend::SEEN_AND_PENDING]);


        return $this->apiResponse($requestNew + $requestPending, null, 200, 0);

    }

    public function approveRequest(Request $request)
    {
        $this->checkLang($request);
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $errors = collect([]);
            foreach ($validator->messages()->all() as $item) {
                $errors->push($item);
            }
            return $this->apiResponse(null, $errors, 422, 0);
        }

        $requestFriend = Friend::where('id', $request->request_id)
            ->whereIn('status', [Friend::REQUEST_FRIEND, Friend::SEEN_AND_PENDING])
            ->first();

        if ($requestFriend != null) {

            $requestFriend->update(['status' => Friend::APPROVED]);

            switch ($request->header('lang')) {
                case 'en':
                    $message = 'The request has been approved';
                    break;
                case 'ar':
                    $message = "تمت الموافقة على الطلب";
                    break;
                default:
                    $message = 'The request has been approved';
                    break;
            }

        } else {
            switch ($request->header('lang')) {
                case 'en':
                    $message = 'Request not found or You were already friends';
                    break;
                case 'ar':
                    $message = " لم يتم العثور على الطلب أو أنتما صديقان بالفعل";
                    break;
                default:
                    $message = 'Request not found or You were already friends';
                    break;
            }
        }


        return $this->apiResponse(null, $message, 200, 0);

    }

    public function disapproveRequest(Request $request)
    {
        $this->checkLang($request);
        $validator = Validator::make($request->all(), [
            'request_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            $errors = collect([]);
            foreach ($validator->messages()->all() as $item) {
                $errors->push($item);
            }
            return $this->apiResponse(null, $errors, 422, 0);
        }

        $requestFriend = Friend::where('id', $request->request_id)
            ->whereIn('status', [Friend::REQUEST_FRIEND, Friend::SEEN_AND_PENDING])
            ->first();
        if ($requestFriend != null) {

            $requestFriend->update(['status' => Friend::DISAPPROVE]);

            switch ($request->header('lang')) {
                case 'en':
                    $message = 'The request has been disapproved';
                    break;
                case 'ar':
                    $message = "تم رفض الطلب";
                    break;
                default:
                    $message = 'The request has been disapproved';
                    break;
            }

        } else {
            switch ($request->header('lang')) {
                case 'en':
                    $message = 'Request not found or You were already friends';
                    break;
                case 'ar':
                    $message = " لم يتم العثور على الطلب أو أنتما صديقان بالفعل";
                    break;
                default:
                    $message = 'Request not found or You were already friends';
                    break;
            }
        }


        return $this->apiResponse(null, $message, 200, 0);

    }


}
