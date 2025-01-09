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
    $url = admin_url("admin.php?page=wcuitgbot");
    $msg = "*PeproDev Ultimate Invoice TelegramBot*\n\n_To send Invoice PDFs here, you just need to:_\n\n".
    "1️⃣ Open [Setting page]($url) in host site\n2️⃣ Add below ID to the Recipient List\n3️⃣ ChatID: `$chat_id`";
    return $this->replyToChat($msg, array(
      "parse_mode"               => "markdown",
      "reply_to_message_id"      => $message->getMessageId(),
      "protect_content"          => false,
      "disable_web_page_preview" => true,
    ));
  }
}
