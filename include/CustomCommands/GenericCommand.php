<?php
/*
* @Author: Amirhossein Hosseinpour <https://amirhp.com>
* @Last modified by: amirhp-com <its@amirhp.com>
* @Last modified time: 2024/09/11 23:05:24
*/

# Generic command Gets executed for generic commands, when no other appropriate one is found.
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class GenericCommand extends SystemCommand {
  protected $name = 'generic';
  protected $description = 'Handles generic commands or is executed by default when a command is not found';
  protected $version = '4.9.0';
  public function execute(): ServerResponse {
    $message = $this->getMessage();
    $command = is_object($message) ? (property_exists($message, "getCommand") ? $message->getCommand() : "") : "";
    $chat_id = $message->getChat()->getId();
    $msg = "*Command not recognized.*
This bot is not meant for chatting or support.
To get started, use:
/about – Learn what this bot does
/setup – Connect your Telegram to WordPress
Need help? Tap the button below ⬇️";
    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId(),
      "protect_content"          => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => [[["text"=>"🛟 Need Help? Get Instant Support", "url"=>"https://t.me/pigment_dev"]]]],
      ));
  }
}
