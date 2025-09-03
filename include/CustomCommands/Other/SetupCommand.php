<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/24 15:54:46
 */

namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class SetupCommand extends UserCommand {
  protected $name        = 'setup';
  protected $description = 'Show Setup Help';
  protected $usage       = '/setup';
  protected $version     = '1.2.0';
  public function execute(): ServerResponse {
    $message = $this->getMessage();
    $chat_id = $message->getChat()->getId();
    $msg = "👋 Hi there!

***Welcome to Ultimate Integration for Telegram***
_Easily connect your WordPress site to Telegram_

*To get started:*
1️⃣ Add this bot as an *Administrator* to your group or channel.
2️⃣ Send the /setup command, or simply *forward a message* from the group/channel to this bot to get the Chat ID.
3️⃣ Go to your website’s *Settings*, and add the Chat ID to the list.

🆔 *Current Chat ID:* `{$chat_id}`
_this chat id is unique and private, tap and copy it._

🚫 _Do not share *Chat ID* with anyone — it may receive sensitive site data and notifications._";

    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId() ?? null,
      "protect_content"          => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => [[["text"=>"🛟 Need Help? Get Instant Support", "url"=>"https://t.me/pigment_dev"]]]],
    ));
  }
}
