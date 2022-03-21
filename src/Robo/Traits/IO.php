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
        $io->block($text, null, "options=bold;", $char);
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
        $decor = "<fg=cyan;options=bold;>" . str_repeat('-', $len) . "</fg=cyan;options=bold>";
        $this->writeln($decor);
        $io->writeln("<fg=cyan;options=bold;>$char $text</fg=cyan;options=bold;>");
        $this->writeln($decor);
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
        $char = $this->decorationCharacter('[NOTE]', 'ℹ[NOTE] ');
        if ($skip) {
            $text = "$text Skiping....";
        }
        $io->block($text, null, "fg=blue;options=bold;", $char);
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
        $char = $this->decorationCharacter('![WARNING]', '⚠️  [WARNING] ');
        $io->block($text, null, "fg=yellow;options=bold;", $char);
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
        $char = $this->decorationCharacter('>', '✅ [OK] ');
        $io->block($text, null, "fg=green;options=bold;", $char);
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
        $char = $this->decorationCharacter('>', '❌ [ERROR] ');
        $io->block($text, null, "fg=red;options=bold;", $char);
    }
}
