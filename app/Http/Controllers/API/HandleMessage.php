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
            $stickerId = getStickerFromContext($contextId)->get('stickerId');
            addTagsToSticker($contextId, $tags);
        }
    
        if ($isSendingSticker) {
            \Log::info("a sticker message");
            $stickerId = $message['sticker']['id'] ?? null;
            saveSticker($messageId, $from, $stickerId, []);
        }
    }

    function searchSticker($from, $tags) {
        \Log::info("Searching stickers for $from with tags: " . implode(', ', $tags));
        return [
            ['from' => $from, 'stickerId' => '1002815198413407']
        ];
    }
    
    function sendMessage($to, $message) {
        \Log::info("Sending message to $to: " . json_encode($message));
    }
    
    function getStickerFromContext($contextId) {
        \Log::info("Fetching sticker for context ID: $contextId");
        return collect(['stickerId' => '123456789']);
    }
    
    function addTagsToSticker($contextId, $tags) {
        \Log::info("Adding tags to sticker $contextId: " . implode(', ', $tags));
    }
    
    function saveSticker($messageId, $from, $stickerId, $tags) {
        \Log::info("Saving sticker $stickerId from $from with tags: " . implode(', ', $tags));
    }

    
}
/*
const { insertDoc, getDoc, addTagsToDoc, searchDocs } = require('./firestore.js');

const addTagsToSticker = async function(id, tags){
  addTagsToDoc(id, tags);
};

const saveSticker = async function(messageId, from, stickerId, tags){
  insertDoc(messageId, from, stickerId, tags)
};

const getStickerFromContext = async function(contextId){
  const sticker = await getDoc(contextId);
  return sticker;
};

const searchSticker = async function(from, tags){
  const stickers = (await searchDocs(from, tags));
  return stickers;
}

=========================

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
});

// Create a new client
const firestore = admin.firestore();

async function getDoc(id) {
  const stickers = firestore.collection("stickers").doc(id);
  const sticker = await stickers.get();
  return sticker;
}

async function insertDoc(messageId, from, stickerId, tags) {
  const document = firestore.doc(`stickers/${messageId}`);
  document.set({
    id: messageId,
    stickerId,
    from,
  });
}

async function addTagsToDoc(id, tags) {
  try {
    const document = firestore.collection(`stickers`).doc(id);
    await document.update({
      tags: tags,
    });
    console.log(`${id} - updated - with: ${JSON.stringify(tags)}`);
  } catch (err) {
    console.error(err);
  }
}

async function searchDocs(from, tags) {
  try {
    const querySnapshot = await firestore
      .collection('stickers')
      .where('from', "==", from)
      .where('tags', "array-contains-any", tags)
      .get();

    if (querySnapshot.empty) {
      console.log("No matching documents found.");
      return [];
    }

    const results = [];
    querySnapshot.forEach((doc) => {
      results.push({ id: doc.id, ...doc.data() });
    });

    return results;
  } catch (err) {
    console.error(err);
  }
}



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