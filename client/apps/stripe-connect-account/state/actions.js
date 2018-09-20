export const STRIPE_CONNECT_ACCOUNT_RELOAD_PAGE = 'STRIPE_CONNECT_ACCOUNT_RELOAD_PAGE';

export const reloadPage = ( dispatch ) => {
    window.location.reload( true );
    dispatch( { type: STRIPE_CONNECT_ACCOUNT_RELOAD_PAGE } );
};
