<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/01/18 15:41:23
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
    $msg = "Hi there! 👋\n\n*Welcome to BlackSwan - Telegram Notifications*\n" .
      "Seamlessly connect your WordPress site & WooCommerce store to Telegram.\n\n" .
      "With our Plugin, you’ll receive instant, real-time notifications for important events like new WooCommerce orders, user registrations, and WordPress emails. Replace traditional email notifications with fast and customizable Telegram messages tailored to your needs.\n\n" .
      "Receive notifications wherever you want:\n" .
      "- In *groups* or *channels* by adding this bot as an Administrator.\n" .
      "- Directly in this *private chat* with the bot for instant updates.\n\n" .
      "To get started:\n" .
      "1️⃣ Add me as an *Administrator* to your group or channel.\n" .
      "2️⃣ Send the command /setup to get the *Chat ID*.\n" .
      "3️⃣ Go to the *Settings* on your site & add it to the list.\n\n" .
      "🆔 *Your Chat ID:* `{$chat_id}` _(specific to this chat)_\n\n" .
      "👨‍🔧 For support or questions, contact: [@amirhp_com](https://t.me/amirhp_com)";

    $markup = array(
      array(
        ["text" => "👨‍🔧 Support", "url"  => "https://t.me/amirhp_com"],
        ["text" => "😍 Developer", "url"  => "https://amirhp.com/landing"],
      ),
      array(
        ['text' => "💻 Contribute (Github)", "url" => "https://github.com/blackswandevcom/blackswan-telegram"],
        ['text' => "🍺 Buy me a Beer (Donate)", "url" => "https://amirhp.com/contact#payment"],
      ),
      array(
        ['text' => "🌏 BlackSwan - Telegram Notification", "url" => "https://wordpress.org/plugins/blackswan-telegram/"],
      ),
    );

    return $this->replyToChat($msg, array(
      "parse_mode" => "markdown",
      "reply_to_message_id" => $this->getMessage()->getMessageId(),
      "protect_content" => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => $markup],
    ));
  }
}
