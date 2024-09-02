<x-global::forms.select id='storypoints' name='storypoints' search="false">
    <x-global::forms.select.option
        :value="''">
        {{  __('label.effort_not_defined') }}
    </x-global::forms.select.option>
    @foreach ($efforts as $effortKey => $effortValue)

        <x-global::forms.select.option
            :value="strtolower($effortKey)"
            :selected="strtolower($effortKey) == strtolower($ticket->storypoints ?? '') ? 'true' : 'false'">
            {{  $effortValue }}
        </x-global::forms.select.option>
    @endforeach
</x-global::forms.select>