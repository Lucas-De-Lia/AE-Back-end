<style>
    .button-background {
        background: linear-gradient(120deg, rgba(255, 203, 2, 0.631) 0%, rgba(255, 116, 2, 0.631) 33%, rgba(228, 33, 83, 0.631) 66%, rgba(60, 58, 229, 0.631) 100%);
        border-radius: 5px;
    }
</style>

@props(['url', 'color' => 'primary', 'align' => 'center'])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="{{ $align }}">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="{{ $align }}">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td class="button-background">
                                    <a href="{{ $url }}" class="button button-{{ $color }}"
                                        target="_blank" rel="noopener">{{ $slot }}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
