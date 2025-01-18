<?php
# @Author: amirhp-com <its@amirhp.com>
# @Last modified by:   amirhp-com <its@amirhp.com>
# @Last modified time: 2022/10/13 00:47:58
# Channel post command Gets executed when
# a new post is created in a channel.
namespace Longman\TelegramBot\Commands\SystemCommands;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
class ChannelpostCommand extends SystemCommand
{
  protected $name        = 'channelpost';
  protected $description = 'Handle channel post';
  protected $version     = '1.1.0';
  public function execute(): ServerResponse
  {
    $message = $this->getChannelPost();
    if ($message->getText() == "/setup") {
      $chat_id = $message->getChat()->getId();
      $msg = "*BlackSwan - Telegram Notification*\n\n_To send notifications here, you just need to:_\n\n".
      "1️⃣ Open Setting in host site\n2️⃣ Add below ID to the ChatID List".
      "\n🆔 ChatID: `$chat_id`\n\n👨‍🔧 Support: @ahosseinhp";
      return Request::editMessageText([
          "chat_id"                  => $chat_id,
          "message_id"               => $message->getMessageId(),
          "text"                     => $msg,
          "parse_mode"               => "markdown",
          "protect_content"          => false,
          "disable_web_page_preview" => true,
      ]);
    }
    return parent::execute();
  }
}
