@extends('header')

@section('content')


<center>
    {!! Button::success(trans('texts.add_company'))->asLinkTo('/login?new_company=true') !!}
</center>

<p>&nbsp;</p>

<div class="row">
    <div class="col-md-6 col-md-offset-3">
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="panel panel-default">
            <div class="panel-body">
            <table class="table table-striped">
            @foreach (Session::get(SESSION_USER_ACCOUNTS) as $account)
                <tr>
                    <td>
                    @if (isset($account->logo_path))
                        {!! HTML::image($account->logo_path.'?no_cache='.time(), 'Logo', ['width' => 100]) !!}
                    @endif
                    </td>                    
                    <td>
                        <h3>{{ $account->account_name }}<br/>
                        <small>{{ $account->user_name }}
                            @if ($account->user_id == Auth::user()->id)
                            | {{ trans('texts.current_user')}}
                            @endif
                        </small></h3>
                    </td>
                    <td>{!! Button::primary(trans('texts.unlink'))->withAttributes(['onclick'=>"return showUnlink({$account->id}, {$account->user_id})"]) !!}</td>
                </tr>
            @endforeach
            </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="unlinkModal" tabindex="-1" role="dialog" aria-labelledby="unlinkModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="myModalLabel">{{ trans('texts.unlink_account') }}</h4>
      </div>

      <div class="container">        
        <h3>{{ trans('texts.are_you_sure') }}</h3>        
      </div>

      <div class="modal-footer" id="signUpFooter">          
        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('texts.cancel') }}</button>
        <button type="button" class="btn btn-primary" onclick="unlinkAccount()">{{ trans('texts.unlink') }}</button>           
      </div>
    </div>
  </div>
</div>


    <script type="text/javascript">
      function showUnlink(userAccountId, userId) {    
        NINJA.unlink = {
            'userAccountId': userAccountId,
            'userId': userId
        };
        $('#unlinkModal').modal('show');    
        return false;
      }

      function unlinkAccount() {    
        window.location = '{{ URL::to('/unlink_account') }}' + '/' + NINJA.unlink.userAccountId + '/' + NINJA.unlink.userId;    
      }

    </script>

@stop