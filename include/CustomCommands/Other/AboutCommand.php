<?php
/*
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/08/02 02:21:20
 */

namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class AboutCommand extends UserCommand {
  protected $name        = 'about';
  protected $description = 'Show about Help';
  protected $usage       = '/about';
  protected $version     = '1.2.0';
  public function execute(): ServerResponse {
    $message = $this->getMessage();
    $chat_id = $message->getChat()->getId();
    $msg =
"*Hi there! 👋*
*Welcome to Ultimate Integration for Telegram*

Take your WordPress & WooCommerce experience to the next level with *instant, real-time Telegram notifications* — faster and more reliable than emails!

*You'll get notified instantly when:*
👤 A new user registers
🛒 A new WooCommerce order is placed
📧 A contact form is submitted
🔔 A new comment is posted
📦 An order status is updated
💰 A payment is completed or fails
✅ A product goes out of stock or comes back
➕ And much more...

*Why you'll love it:*
✨ Super fast and customizable alerts
🔗 Works with channels, groups, or private chats
🔐 Secure — Everything is Open-source
🗂️ Supports macros and smart message templates
🌐 Multi-site compatible
📚 Developer-friendly with tons of hooks & filters
🧪 Test message system included
🕓 24/7 support and documentation available

No more refreshing dashboards or missing emails — just clear, instant messages where you already spend time: *Telegram* 🚀";


    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId(),
      "protect_content"          => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => [[["text"=>"🛟 Need Help? Get Instant Support", "url"=>"https://t.me/pigment_dev"]]]],
    ));
  }
}
