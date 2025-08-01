<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/02 02:21:29
*/

namespace Longman\TelegramBot\Commands\SystemCommands;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class StartCommand extends SystemCommand {
  protected $name = 'start';
  protected $description = 'Start command';
  protected $usage = '/start';
  protected $version = '1.2.0';
  protected $private_only = true;
  public function execute(): ServerResponse {
    // If you use deep-linking, get the parameter like this: @see https://core.telegram.org/bots#deep-linking
    $cmd_line = $this->getMessage()->getText(true);
    $chat_id = $this->getMessage()->getChat()->getId();
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

    $id = "```\n".print_r($this->getMessage()->getChat()->getId(),1)."```";

    return $this->replyToChat($msg, array(
      "reply_to_message_id" => $this->getMessage()->getMessageId(),
      "parse_mode" => "markdown",
      "protect_content" => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => [[["text"=>"🛟 Need Help? Get Instant Support", "url"=>"https://t.me/pigment_dev"]]]],
    ));
  }
}
