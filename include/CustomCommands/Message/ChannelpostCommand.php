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
      $url = admin_url("admin.php?page=wcuitgbot");
      $msg = "*PeproDev Ultimate Invoice TelegramBot*\n\n_To send Invoice PDFs here, you just need to:_\n\n".
      "1️⃣ Open [Setting page]($url) in host site\n2️⃣ Add below ID to the Recipient List\n3️⃣ ChatID: `$chat_id`".
      "\n\n👨‍🔧 Support: @ahosseinhp\n👨‍🔧 Developer: [Amirhp-com](https://amirhp.com/landing)";
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
