<?php

namespace App\Nova\Flexible\Layouts;

use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Whitecube\NovaFlexibleContent\Layouts\Layout;

class FormEmail extends Layout
{
    /**
     * The layout's unique identifier
     *
     * @var string
     */
    protected $name = 'email';

    /**
     * The displayed title
     *
     * @var string
     */
    protected $title = 'Email address';

    /**
     * Get the fields displayed by the layout.
     *
     * @return array
     */
    public function fields()
    {
        return [
            Text::make('Label', 'label')->rules('required'),
            Text::make('Help text', 'help')->nullable(),
            Boolean::make('Required', 'required')
        ];
    }

}
