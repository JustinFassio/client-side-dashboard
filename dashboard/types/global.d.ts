declare global {
  interface Window {
    wp: {
      data: {
        dispatch: any;
      };
    };
    athleteDashboardData: {
      nonce: string;
      siteUrl: string;
      apiUrl: string;
      userId: number;
    };
  }
} 