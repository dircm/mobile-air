<?php

namespace Native\Mobile\Edge\Components\Navigation;

use Native\Mobile\Edge\Components\EdgeComponent;

class BottomNav extends EdgeComponent
{
    protected string $type = 'bottom_nav';

    protected bool $hasChildren = true;

    public function __construct(
        public ?bool $dark = null,
        public string $labelVisibility = 'labeled'
    ) {}

    protected function toNativeProps(): array
    {
        return [
            'dark' => $this->dark,
            'label_visibility' => $this->labelVisibility,
            'id' => 'bottom_nav',
        ];
    }
}
