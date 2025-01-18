<?php
# @Author: amirhp-com <its@amirhp.com>
# @Last modified by:   amirhp-com <its@amirhp.com>
# @Last modified time: 2022/10/13 00:47:45
namespace Longman\TelegramBot\Commands\UserCommands;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
class SetupCommand extends UserCommand
{
  protected $name        = 'setup';
  protected $description = 'Show Setup Help';
  protected $usage       = '/setup';
  protected $version     = '1.2.0';
  public function execute(): ServerResponse
  {
    $message = $this->getMessage();
    $chat_id = $message->getChat()->getId();
    $msg = "*BlackSwan - Telegram Notification*\n\n_To send notifications here, you just need to:_\n\n".
    "1️⃣ Open Setting in host site\n2️⃣ Add below ID to the ChatID List".
    "\n🆔 ChatID: `$chat_id`\n\n👨‍🔧 Support: @ahosseinhp";
    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId(),
      "protect_content"          => false,
      "disable_web_page_preview" => true,
    ));
  }
}
