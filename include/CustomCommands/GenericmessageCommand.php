<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/24 16:49:57
*/

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Entity;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends SystemCommand {
    protected $name = 'genericmessage';
    protected $description = 'Handle non-command messages (including forwarded)';
    protected $version = '1.0.0';

    public function execute(): ServerResponse {
        $message = $this->getMessage();
        $forward_from        = $message->getForwardFrom();        // original user (if any)
        $forward_from_chat   = $message->getForwardFromChat();    // original chat/channel (if any)
        $forward_sender_name = $message->getForwardSenderName();  // name when original sender hidden
        if ($forward_from_chat) {
            $chat_title = Entity::escapeMarkdown($forward_from_chat->getTitle() ?? ""); // title of original chat/channel
            $chat_id = Entity::escapeMarkdown($forward_from_chat->getId() ?? ""); // title of original chat/channel
            $username = $forward_from_chat->getUsername() ? "@".Entity::escapeMarkdown($forward_from_chat->getUsername()) : "this channel/chat";
            $msg = "Forward Detected, to connect to ***{$chat_title}***:\n"
                . "\n1️⃣ Add this bot as an *Administrator* to {$username}, with permission to *Post Messages*."
                . "\n\n2️⃣ Go to your website’s *Settings*, and add the Chat ID to the list."
                . "\n\n🆔 *Forwarded Chat's ID:* `{$chat_id}`"
                . "\n\n_this chat id is unique and private, tap and copy it._"
                . "\n\n🚫 _Do not share *Chat ID* with anyone — it may receive sensitive site data and notifications._";
            return $this->replyToChat($msg, array(
                "reply_to_message_id" => $this->getMessage()->getMessageId() ?? null,
                "parse_mode" => "markdown",
                "protect_content" => true,
                "disable_web_page_preview" => true,
                "reply_markup" => ["inline_keyboard" => [[["text" => "🛟 Need Help? Get Instant Support", "url" => "https://t.me/pigment_dev"]]]],
            ));
        }elseif ($forward_from) {
            $msg = "Forward Detected, but from a private user/bot.\n\n"
                . "To connect to a *Channel* or *Group*, please forward a message from there.\n\n"
                . "If you need help, tap the button below.";
            return $this->replyToChat($msg, array(
                "reply_to_message_id" => $this->getMessage()->getMessageId() ?? null,
                "parse_mode" => "markdown",
                "protect_content" => true,
                "disable_web_page_preview" => true,
                "reply_markup" => ["inline_keyboard" => [[["text" => "🛟 Need Help? Get Instant Support", "url" => "https://t.me/pigment_dev"]]]],
            ));
        }elseif ($forward_sender_name) {
            $msg = "Forward Detected, but the sender's name is hidden.\n\n"
                . "To connect to a *Channel* or *Group*, please forward a message from there.\n\n"
                . "If you need help, tap the button below.";
            return $this->replyToChat($msg, array(
                "reply_to_message_id" => $this->getMessage()->getMessageId() ?? null,
                "parse_mode" => "markdown",
                "protect_content" => true,
                "disable_web_page_preview" => true,
                "reply_markup" => ["inline_keyboard" => [[["text" => "🛟 Need Help? Get Instant Support", "url" => "https://t.me/pigment_dev"]]]],
            ));
        }
        // if message is not forwarded then do the default command and etc
        return Request::emptyResponse();
    }
}
