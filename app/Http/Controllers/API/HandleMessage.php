<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappSticker;

class HandleMessage extends Controller
{
    public function __invoke(Request $request) {
        \Log::info("Incoming webhook: " . json_encode($request->all()));
    
        $message = $request->input('entry.0.changes.0.value.messages.0');
    
        $from = $message['from'] ?? null;
        $messageType = $message['type'] ?? null;
        $messageId = $message['id'] ?? null;
        $contextId = $message['context']['id'] ?? null;
    
        $isLookingForSticker = $messageType === 'text' && !$contextId;
        $isAddingStickerTags = $messageType === 'text' && $contextId;
        $isSendingSticker = $messageType === 'sticker';
    
        if ($isLookingForSticker) {
            \Log::info(json_encode($from). "---is looking for sticker");
            $tags = explode(' ', $message['text']['body'] ?? '');
            $stickers = $this->searchSticker($from, $tags);
            
            foreach ($stickers as $sticker) {
                $this->sendMessage("541115" . substr($sticker['from'], -8), ['sticker' => $sticker['stickerId']]);
            }
        }
    
        if ($isAddingStickerTags) {
            $tags = explode(' ', $message['text']['body'] ?? '');
            $sticker = WhatsappSticker::firstWhere('context_id', $contextId);
            $stickerId = $sticker->sticker_id;
            $this->addTagsToSticker($contextId, $tags);
        }
    
        if ($isSendingSticker) {
            \Log::info("a sticker message");
            $stickerId = $message['sticker']['id'] ?? null;
            $this->saveSticker($messageId, $from, $stickerId, []);
        }
    }

    function searchSticker($from, $tags) {

        $results = WhatsappSticker::where('from', $from)->where('tags', 'like', '%'.implode(', ', $tags).'%')->get();

        \Log::debug(json_encode($results));
    }
    

    function addTagsToSticker($context_id, $tags) {
        $sticker = WhatsappSticker::firstWhere('context_id', $context_id);
        $sticker->tags = tags;
        $sticker->save();
    }
    
    function saveSticker($messageId, $from, $stickerId, $tags) {
        $message = new WhatsappSticker();
        $message->sticker_id = $stickerId;
        $message->message_id = $messageId;
        $message->tags = implode(', ', $tags);
        $message->from = $from;
        $message->save();
    }


    function sendMessage($to, $message)
    {
        \Log::info("Sending message to $to: " . json_encode($message));

        $body = [
            'to' => $to
        ];

        if (isset($message['sticker'])) {
            $body['type'] = 'sticker';
            $body['sticker'] = [
                'id' => $message['sticker']
            ];
        }

        $this->sendRequest($body);
    }

    function sendRequest($body)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('WPP_API_TOKEN'),
            'Content-Type' => 'application/json',
        ])
        ->post(env('WPP_API_ENDPOINT') . '/messages', [
            'messaging_product' => 'whatsapp',
            ...$body
        ]);

        $data = $response->json();
        \Log::info('Response sending message', $data);
    }


    
}
/*

===================================== meta proxy

const sendMessage = async function (to, message) {
  const body = {
    to: to
  }
  if(message.sticker){
    body['type'] = 'sticker';
    body['sticker'] = {
      id: message.sticker
    }
  }
  
  sendRequest(body);
}

async function sendRequest(body) {
   const res = await fetch(`${process.env.WPP_API_ENDPOINT}/messages`, {
    method: "post",
    headers: {
      Authorization: `Bearer ${process.env.WPP_API_TOKEN}`,
      'Content-Type': "application/json",
    },
    body: JSON.stringify({
     messaging_product: "whatsapp",
     ...body
    }),
  });
  const data = await res.json();
  console.log("res sending email", data);
}

*/