<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/03/08 14:38:54
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
    $msg = "Hi there! 👋\n\n*Welcome to Ultimate Integration for Telegrams*\n" .
      "Seamlessly connect your WordPress site & WooCommerce store to Telegram.\n\n" .
      "With our Plugin, you’ll receive instant, real-time notifications for important events like new WooCommerce orders, user registrations, and WordPress emails. Replace traditional email notifications with fast and customizable Telegram messages tailored to your needs.\n\n".
      "\[ Developed by Amirhossein Hosseinpour [(amirhp.com)](https://amirhp.com/) ]";
    $markup = array(
      array(
        ['text' => "💻 Contribute (Github)", "url" => "https://github.com/pigment-dev/ultimate-integration-for-telegram"],
        ['text' => "🍺 Buy me a Beer (Donate)", "url" => "https://amirhp.com/contact#payment"],
      ),
      array(
        ['text' => "🌏 Ultimate Integration for Telegram", "url" => "https://wordpress.org/plugins/ultimate-integration-for-telegram/"],
      ),
    );

    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId(),
      "protect_content"          => true,
      "disable_web_page_preview" => true,
      "reply_markup" => ["inline_keyboard" => $markup],
    ));
  }
}
