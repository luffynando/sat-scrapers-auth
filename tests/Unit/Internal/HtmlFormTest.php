<?php

declare(strict_types=1);

use SatScrapersAuth\Internal\HtmlForm;

describe('HtmlForm', function (): void {
    test('get form values', function (): void {
        $form = '<form>';
        $form .= '<input name="key" value="value">';
        $form .= '<select name="otherKey">';
        $form .= '<option value="option1">option1</option>';
        $form .= '<option value="option2">option2</option>';
        $form .= '</select>';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->getFormValues();
        expect($elements)->toMatchArray([
            'key' => 'value',
            'otherKey' => '',
        ]);
    });

    test('read input values', function (): void {
        $form = '<form>';
        $form .= '<input name="key" value="value">';
        $form .= '<select name="otherKey">';
        $form .= '<option value="option1">option1</option>';
        $form .= '<option value="option2">option2</option>';
        $form .= '</select>';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readInputValues();
        expect($elements)->toMatchArray([
            'key' => 'value',
        ]);
    });

    test('read select values', function (): void {
        $form = '<form>';
        $form .= '<input name="key" value="value">';
        $form .= '<select name="otherKey">';
        $form .= '<option value="option1">option1</option>';
        $form .= '<option value="option2">option2</option>';
        $form .= '</select>';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readSelectValues();
        expect($elements)->toMatchArray([
            'otherKey' => '',
        ]);
    });

    test('read select values with selected', function (): void {
        $form = '<form>';
        $form .= '<input name="key" value="value">';
        $form .= '<select name="otherKey">';
        $form .= '<option value="option1">option1</option>';
        $form .= '<option value="option2" selected>option2</option>';
        $form .= '</select>';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readSelectValues();
        expect($elements)->toMatchArray([
            'otherKey' => 'option2',
        ]);
    });

    test('read select values with excluded', function (): void {
        $form = '<form>';
        $form .= '<select name="foo">';
        $form .= '<option value="x-foo" selected>x-foo</option>';
        $form .= '<option value="x-bar">x-bar</option>';
        $form .= '</select>';
        $form .= '<select name="bar">';
        $form .= '<option value="x-foo">x-foo</option>';
        $form .= '<option value="x-bar" selected>x-bar</option>';
        $form .= '</select>';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form', ['/^foo$/']);
        $elements = $htmlForm->readSelectValues();
        expect($elements)->toMatchArray([
            'bar' => 'x-bar',
        ]);
    });

    test('read form elements values without element', function (): void {
        $form = '<form>';
        $form .= '<input name="key" value="value">';
        $form .= '<select name="otherKey">';
        $form .= '<option value="option1">option1</option>';
        $form .= '<option value="option2">option2</option>';
        $form .= '</select>';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readFormElementsValues('textarea');
        expect($elements)->toHaveLength(0);
    });

    test('read form elements values with element', function (): void {
        $form = '<form>';
        $form .= '<input name="key" value="myValue">';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readFormElementsValues('input');
        expect($elements)->toMatchArray([
            'key' => 'myValue',
        ]);
    });

    test('read form elements values out of the parent element', function (): void {
        $form = '<form>';
        $form .= '</form>';
        $form .= '<input name="key" value="myValue">';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readFormElementsValues('input');
        expect($elements)->toHaveLength(0);
    });

    test('read input values radios', function (): void {
        $form = '<form>';
        $form .= '<input name="foo" type="radio" value="1">';
        $form .= '<input name="foo" type="radio" value="2" checked="checked">';
        $form .= '<input name="bar" type="radio" value="1" checked>';
        $form .= '<input name="bar" type="radio" value="2">';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readInputValues();
        expect($elements)->toMatchArray([
            'foo' => '2',
            'bar' => '1',
        ]);
    });

    test('read form elements values ignoring type', function (): void {
        $form = '<form>';
        $form .= '<input name="hide" type="hidden" value="x-hidden">';
        $form .= '<input name="show" type="text" value="x-text">';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form');
        $elements = $htmlForm->readFormElementsValues('input', ['hidden']);
        expect($elements)->toMatchArray([
            'show' => 'x-text',
        ]);
    });

    test('read form elements values ignoring name pattern', function (): void {
        $form = '<form>';
        $form .= '<input name="ignore_1" value="">';
        $form .= '<input name="ignore_2" value="">';
        $form .= '<input name="no-ignore" value="">';
        $form .= '</form>';

        $htmlForm = new HtmlForm($form, 'form', ['/^ignore.+/']);
        $elements = $htmlForm->readFormElementsValues('input');
        expect($elements)->toMatchArray([
            'no-ignore' => '',
        ]);
    });
});
