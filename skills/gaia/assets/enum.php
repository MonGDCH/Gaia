<?php

declare(strict_types=1);

namespace app\contract\enum;

/**
 * {{DESC}} 枚举
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0 {{DATE}}
 */
enum {{NAME}}Enum: int
{
    /** 禁用 */
    case Status_Disable = 0;

    /** 启用 */
    case Status_Enable = 1;

    /**
     * 获取文本描述
     *
     * @return string
     */
    public function text(): string
    {
        return match ($this) {
            self::Status_Disable => '禁用',
            self::Status_Enable  => '启用',
        };
    }

    /**
     * 获取全部选项
     *
     * @return array
     */
    public static function options(): array
    {
        $result = [];
        foreach (self::cases() as $case) {
            $result[] = ['value' => $case->value, 'text' => $case->text()];
        }

        return $result;
    }
}
