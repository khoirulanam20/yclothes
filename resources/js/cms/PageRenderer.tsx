import { Render } from '@measured/puck';
import '@measured/puck/puck.css';
import { puckConfig, type PuckData, emptyPuckData } from '@/cms/puckConfig';

type Props = {
    layoutJson?: PuckData | null;
    pageTitle?: string;
};

export function PageRenderer({ layoutJson, pageTitle }: Props) {
    const data: PuckData =
        layoutJson && layoutJson.content?.length
            ? {
                  ...layoutJson,
                  root: {
                      ...layoutJson.root,
                      props: {
                          showBreadcrumb: true,
                          pageTitle: pageTitle ?? (layoutJson.root?.props?.pageTitle as string) ?? '',
                          ...layoutJson.root?.props,
                      },
                  },
              }
            : emptyPuckData;

    return <Render config={puckConfig} data={data} />;
}

export default PageRenderer;
