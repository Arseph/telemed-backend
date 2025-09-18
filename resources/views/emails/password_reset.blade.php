<center style="background-color:#E1E1E1;">
    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTbl" style="table-layout: fixed;max-width:100% !important;width: 100% !important;min-width: 100% !important;">
      <tr>
        <td align="center" valign="top" id="bodyCell">
          <table bgcolor="#FFFFFF" border="0" cellpadding="0" cellspacing="0" width="500" id="emailBody">

            <tr>
              <td align="center" valign="top">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="color:#FFFFFF;" bgcolor="#2E7D32">
                  <tr>
                    <td align="center" valign="top">
                      <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer">
                        <tr>
                          <td align="center" valign="top" width="500" class="flexibleContainerCell">
                            <table border="0" cellpadding="30" cellspacing="0" width="100%">
                              <tr>
                                <td align="center" valign="top" class="textContent">
                                  <h1 style="text-align:center;font-weight:normal;font-family:Helvetica,Arial,sans-serif;font-size:23px;margin-bottom:10px;color:#C9BC20;line-height:135%;">DOH XII EMPLOYEE PORTAL</h1>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td align="center" valign="top">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td align="center" valign="top">
                      <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer">
                        <tr>
                          <td align="center" valign="top" width="500" class="flexibleContainerCell">
                            <table border="0" cellpadding="30" cellspacing="0" width="100%">
                              <tr>
                                <td align="left" valign="top">

                                  <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                      <td valign="top" class="textContent">
                                        <p>Hello!</p>
                                        <p></p>
                                        <p></p>
                                        <p>You are receiving this email because we received a password reset request for your account.</p>
                                        <br>
                                        <br>
                                        <div>
                                            <center>
                                                <a style="background-color: #285C4D;border: none;color: white;padding: 15px 32px;text-align: center;text-decoration: none;display: inline-block;font-size: 10px;margin: 4px 2px;cursor: pointer;border-radius: 10px;"\
                                                href="{{ config('app.frontend_url')}}/reset-password/{{$token}}/{{$email}}"
                                                >
                                                  Reset Password
                                                </a>
                                            </center>
                                        </div>
                                        <br>
                                        <div>If you did not request a password reset, no further action is required.</div>
                                        <br>
                                        <div>
                                            If you’re having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
                                            <a href="{{ config('app.frontend_url')}}/reset-password/{{$token}}/{{$email}}">{{ config('app.frontend_url')}}/reset-password/{{$token}}/{{$email}}</a>
                                        </div>
                                        <br>
                                        <div><small>Department of Health - CHD SOCCSKSARGEN Region</small></div>
                                        <div><small>Prk. San Miguel, Brgy. Paraiso, Koronadal City, South Cotabato</small></div>
                                      </td>
                                    </tr>
                                  </table>

                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

          </table>

          <!-- footer -->
          <table bgcolor="#E1E1E1" border="0" cellpadding="0" cellspacing="0" width="500" id="emailFooter">
            <tr>
              <td align="center" valign="top">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td align="center" valign="top">
                      <table border="0" cellpadding="0" cellspacing="0" width="500" class="flexibleContainer">
                        <tr>
                          <td align="center" valign="top" width="500" class="flexibleContainerCell">
                            <table border="0" cellpadding="30" cellspacing="0" width="100%">
                              <tr>
                                <td valign="top" bgcolor="#E1E1E1">

                                  <div style="font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#828282;text-align:center;line-height:120%;">
                                    <div>Copyright &#169; 2024. All rights reserved.</div>
                                    <label>------------PLEASE DO NOT REPLY------------</label>
                                  </div>

                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
          <!-- // end of footer -->

        </td>
      </tr>
    </table>
  </center>
