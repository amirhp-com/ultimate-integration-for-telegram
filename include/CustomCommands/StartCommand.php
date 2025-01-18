<?php
/*
 * @Author: Amirhossein Hosseinpour <https://amirhp.com>
 * @Last modified by: amirhp-com <its@amirhp.com>
 * @Last modified time: 2025/01/18 06:55:10
*/
namespace Longman\TelegramBot\Commands\SystemCommands;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
class StartCommand extends SystemCommand
{
  protected $name = 'start';
  protected $description = 'Start command';
  protected $usage = '/start';
  protected $version = '1.2.0';
  protected $private_only = true;
  public function execute(): ServerResponse
  {
    // If you use deep-linking, get the parameter like this: @see https://core.telegram.org/bots#deep-linking
    $cmd_line = $this->getMessage()->getText(true);
    $chat_id = $this->getMessage()->getChat()->getId();
    $msg = "Hi there! 👋\n".
    "Welcome to *BlackSwan - Telegram Notification*\n\n".
    "_I will send you Customized WordPress/WooCommerce Notifications created on my host site:_\n`".home_url()."`\n\n".
    "_You can add me to any Group/Channel and I will send messages there, you just need to:_\n\n".
    "1️⃣ Add me as an administrator\n2️⃣ Send /setup to get ChatID\n3️⃣ Open Setting in host site & Add ChatID to List\n🆔 ChatID: `$chat_id`\n\n".
    "👨‍🔧 Support: [@amirhp_com](t.me/ahosseinhp)";
    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $this->getMessage()->getMessageId(),
      "protect_content"          => false,
      "disable_web_page_preview" => true,
    ));
  }
}
