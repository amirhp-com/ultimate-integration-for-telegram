<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/01/20 11:46:24
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
    $msg = "Hi there! 👋\n\n*Welcome to Ultimate Telegram Integrations*\n" .
      "Seamlessly connect your WordPress site & WooCommerce store to Telegram.\n\n" .
      "To get started:\n" .
      "1️⃣ Add me as an *Administrator* to your group or channel.\n" .
      "2️⃣ Go to the *Settings* on your site & add Chat ID to the list.\n" .
      "🆔 *Your Chat ID:* `{$chat_id}` _(specific to this chat)_";

      $markup = array(array(
        ["text" => "👨‍🔧 Support", "url"  => "https://t.me/amirhp_com"],
        ['text' => "🍺 Buy me a Beer (Donate)", "url" => "https://amirhp.com/contact#payment"],
      ));

    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId(),
      "protect_content"          => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => $markup],
    ));
  }
}
