<?php

namespace Specbee\DevSuite\Robo\Traits;

use Symfony\Component\Console\Style\SymfonyStyle;
use Robo\Common\InputAwareTrait;
use Robo\Common\OutputAwareTrait;

trait IO
{
    use InputAwareTrait;
    use OutputAwareTrait;

    /**
     * @param string $nonDecorated
     * @param string $decorated
     *
     * @return string
     */
    protected function decorationCharacter($nonDecorated, $decorated)
    {
        if (!$this->output()->isDecorated() || (strncasecmp(PHP_OS, 'WIN', 3) == 0)) {
            return $nonDecorated;
        }
        return $decorated;
    }


    /**
     * Write a normal text.
     *
     * @param string $text
     *   The text.
     */
    protected function say($text)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $char = $this->decorationCharacter('>', '➜');
        $io->newLine();
        $io->writeln("<options=bold;>$char $text</options=bold;>");
    }

    /**
     * Write a title text.
     *
     * @param string $text
     *   The text.
     */
    protected function title($text)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $char = $this->decorationCharacter('>', '💡');
        $lines = explode("\n", $text);
        $length = array_reduce(array_map('strlen', $lines), 'max');
        $len = $length + 12;

        $io->newLine();
        $this->writeln("<fg=cyan;options=bold;>" . str_repeat('-', $len) . "</fg=cyan;options=bold>");
        $this->writeln("<fg=cyan;options=bold;>$char $text</fg=cyan;options=bold;>");
        $this->writeln("<fg=cyan;options=bold;>" . str_repeat('-', $len) . "</fg=cyan;options=bold>");
        $io->newLine();
    }

    /**
     * Write an info.
     *
     * @param string $text
     */
    protected function info($text, $skip = false)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $char = $this->decorationCharacter('[NOTE]', 'ℹ[NOTE]');
        $message = "$char   $text";
        if ($skip) {
            $message = "$char $text Skiping....";
        }
        $io->writeln("<fg=blue;options=bold;>$message</fg=blue;options=bold;>");
        $io->newLine();
    }

    /**
     * Confirm an action.
     *
     * @param string $message.
     *   The message.
     */
    protected function confirm($message, $default = true)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $io->confirm("<options=bold>$message</>", $default);
    }

    /**
     * Display a warning.
     *
     * @param string $text
     *   The text.
     */
    protected function warning($text)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $char = $this->decorationCharacter('![WARNING]', '⚠️  [WARNING]');
        $message = "$char   $text";
        $io->writeln("<fg=yellow;options=bold;>$message</fg=yellow;options=bold;>");
        $io->newLine();
    }

    /**
     * Display a warning.
     *
     * @param string $text
     *   The text.
     */
    protected function success($text)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $char = $this->decorationCharacter('>', '✅ [OK]');
        $message = "$char   $text";
        $io->writeln("<fg=green;options=bold;>$message</fg=green;options=bold;>");
        $io->newLine();
    }

    /**
     * Display an error.
     *
     * @param string $text
     *   The text.
     */
    protected function error($text)
    {
        $io = new SymfonyStyle($this->input(), $this->output());
        $char = $this->decorationCharacter('>', '❌ [ERROR]');
        $message = "$char   $text";
        $io->writeln("<fg=red;options=bold;>$message</fg=red;options=bold;>");
        $io->newLine();
    }

    public function myio(SymfonyStyle $io)
    {
        $this->say("This is a normal text");
        $this->title("This is a title");
        $this->info("This is an info", true);
        $this->warning("This is a warning");
        $this->success("This is a success");
        $this->error("This is an error");
    }
}
