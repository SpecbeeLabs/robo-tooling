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
     * @param string $text
     * @param int $length
     * @param string $format
     */
    protected function formattedOutput($text, $length, $format)
    {
        $lines = explode("\n", trim($text, "\n"));
        $maxLineLength = array_reduce(array_map('strlen', $lines), 'max');
        $length = max($length, $maxLineLength);
        $len = $length + 2;
        $space = str_repeat(' ', $len);
        $this->writeln(sprintf($format, $space));
        foreach ($lines as $line) {
            $line = str_pad($line, $length, ' ', STR_PAD_RIGHT);
            $this->writeln(sprintf($format, " $line "));
        }
        $this->writeln(sprintf($format, $space));
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
        $io->writeln("$char $text");
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
        $char = $this->decorationCharacter('>', '➜');
        $io->section("$char $text");
    }

    /**
     * Write an info.
     *
     * @param string $text
     */
    protected function info($text, $skip = false)
    {
        $char = $this->decorationCharacter('>>', '>>');
        $message = "$char $text";
        if ($skip) {
            $message = "$char $text Skiping....";
        }
        $format = "<fg=white;bg=green;options=bold>%s</fg=white;bg=green;options=bold>";
        $this->formattedOutput($message, 40, $format);
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
        $char = $this->decorationCharacter('!!', '!!');
        $message = "$char   $text";
        $format = "<fg=black;bg=yellow;options=bold>%s</fg=black;bg=yellow;options=bold>";
        $io->newLine();
        $this->formattedOutput($message, 65, $format);
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
        $char = $this->decorationCharacter('>', '✔');
        $message = "$char   $text";
        $format = "<fg=black;bg=green;options=bold>%s</fg=black;bg=green;options=bold>";
        $io->newLine();
        $this->formattedOutput($message, 65, $format);
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
        $char = $this->decorationCharacter('>', '⤫');
        $message = "$char   $text";
        $format = "<fg=white;bg=red;options=bold>%s</fg=white;bg=red;options=bold>";
        $io->newLine();
        $this->formattedOutput($message, 65, $format);
        $io->newLine();
    }
}
