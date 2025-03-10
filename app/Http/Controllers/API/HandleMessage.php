<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
            \Log::info([$from], "---is looking for sticker");
            $tags = explode(' ', $message['text']['body'] ?? '');
            $stickers = searchSticker($from, $tags);
            
            foreach ($stickers as $sticker) {
                sendMessage("541115" . substr($sticker['from'], -8), ['sticker' => $sticker['stickerId']]);
            }
        }
    
        if ($isAddingStickerTags) {
            $tags = explode(' ', $message['text']['body'] ?? '');
            $sticker = WhatsappSticker::firstWhere('context_id', $contextId);
            $stickerId = $sticker->sticker_id;
            addTagsToSticker($contextId, $tags);
        }
    
        if ($isSendingSticker) {
            \Log::info("a sticker message");
            $stickerId = $message['sticker']['id'] ?? null;
            saveSticker($messageId, $from, $stickerId, []);
        }
    }

    function searchSticker($from, $tags) {
        //const stickers = (await searchDocs(from, tags));
        //return stickers;

        $results = WhatsappSticker::whereIn('tags', $tags)->get();

        \Log::debug($results);
    }
    
    function sendMessage($to, $message) {
        \Log::info("Sending message to $to: " . json_encode($message));
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
        $message->tags = $tags;
        $message->from = $from;
        WhatsappMessage::upsert($message, uniqueBy: ['message_id', 'sticker_id'], update: ['tags'])
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