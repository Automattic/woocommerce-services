import React from 'react';
import { translate } from '../mixins/i18n';

/*
 * Simplified version of the "localize" higher order component from i18n-calypso.
 * Instead of injecting translate, moment and numberFormat, it only injects "translate".
 * That's ok for now, if we import a Calypso component that uses moment or numberFormat
 * it will break and we'll have to add those injections here.
 */
export const localize = ( ComposedComponent ) => ( props ) => <ComposedComponent { ...props } translate={ translate } />;
